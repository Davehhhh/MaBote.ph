import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import '../services/session.dart';

class TransactionsScreen extends StatefulWidget {
  const TransactionsScreen({super.key});

  @override
  State<TransactionsScreen> createState() => _TransactionsScreenState();
}

class _TransactionsScreenState extends State<TransactionsScreen> {
  List<Map<String, dynamic>> _transactions = [];
  bool _isLoading = true;
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    _fetchTransactions();
    
    // Refresh transactions every 2 seconds for real-time updates
    _refreshTimer = Timer.periodic(const Duration(seconds: 2), (timer) {
      if (mounted) {
        _fetchTransactions();
      } else {
        timer.cancel();
      }
    });
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _fetchTransactions() async {
    try {
      final uid = await Session.userId();
      if (uid == null) throw Exception('Not logged in');
      
      const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
      final url = Uri.parse('$base/transactions.php?user_id=$uid');
      final res = await http.get(url);
      final data = jsonDecode(res.body) as Map<String, dynamic>;
      
      if (res.statusCode == 200 && data['success'] == true) {
        setState(() {
          _transactions = (data['transactions'] as List).cast<Map<String, dynamic>>();
          _isLoading = false;
        });
      } else {
        throw Exception(data['message'] ?? 'Failed to fetch transactions');
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Transactions')),
      body: RefreshIndicator(
        onRefresh: _fetchTransactions,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _transactions.isEmpty
                ? const Center(child: Text('No transactions yet'))
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: _transactions.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 12),
                    itemBuilder: (context, i) {
                      final t = _transactions[i];
                      final isRedemption = t['transaction_type'] == 'redemption';
                      final points = t['points_earned'] ?? 0;
                      
                      return Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: isRedemption ? const Color(0xFFFFEBEE) : const Color(0xFFEAF3E8), 
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: isRedemption ? Colors.red.shade200 : Colors.green.shade200,
                            width: 1,
                          ),
                        ),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: isRedemption ? Colors.red.shade100 : Colors.green.shade100,
                                shape: BoxShape.circle,
                              ),
                              child: Icon(
                                isRedemption ? Icons.redeem : Icons.local_drink,
                                color: isRedemption ? Colors.red : Colors.green,
                                size: 24,
                              ),
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Text(
                                        t['transaction_code'] ?? 'N/A', 
                                        style: const TextStyle(fontWeight: FontWeight.w700),
                                      ),
                                      Text(
                                        '${points > 0 ? '+' : ''}$points pts',
                                        style: TextStyle(
                                          color: isRedemption ? Colors.red : Colors.green,
                                          fontWeight: FontWeight.w800,
                                          fontSize: 16,
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    isRedemption ? 'Reward Claimed' : 'Bottles: ${t['bottle_deposited'] ?? 0}',
                                    style: TextStyle(color: Colors.grey[600]),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    _formatDate(t['transaction_date'] ?? ''),
                                    style: TextStyle(color: Colors.grey[500], fontSize: 12),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
      ),
    );
  }

  String _formatDate(String dateString) {
    if (dateString.isEmpty) return 'N/A';
    
    try {
      final date = DateTime.parse(dateString);
      final now = DateTime.now();
      final difference = now.difference(date);
      
      if (difference.inDays == 0) {
        return 'Today ${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}';
      } else if (difference.inDays == 1) {
        return 'Yesterday';
      } else if (difference.inDays < 7) {
        return '${difference.inDays} days ago';
      } else {
        return '${date.day}/${date.month}/${date.year}';
      }
    } catch (e) {
      return dateString;
    }
  }
}
