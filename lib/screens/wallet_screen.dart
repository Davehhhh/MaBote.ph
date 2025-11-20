import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import '../services/session.dart';

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  Map<String, dynamic>? _walletData;
  bool _isLoading = true;
  String? _errorMessage;
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    _fetchWalletData();
    
    // Refresh wallet data every 5 seconds for real-time updates
    // Reduced frequency to avoid excessive network requests
    _refreshTimer = Timer.periodic(const Duration(seconds: 5), (timer) {
      if (mounted) {
        _fetchWalletData();
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

  Future<void> _fetchWalletData() async {
    if (!mounted) return; // Don't fetch if widget is disposed
    
    try {
      final uid = await Session.userId();
      if (uid == null) {
        if (!mounted) return;
        setState(() {
          _isLoading = false;
          _walletData = null;
          _errorMessage = 'Not logged in';
        });
        return;
      }
      
      const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
      final url = Uri.parse('$base/get_wallet.php?user_id=$uid');
      
      print('Wallet: Fetching from $url');
      
      http.Response res;
      try {
        res = await http.get(url).timeout(
          const Duration(seconds: 15), // Increased timeout
          onTimeout: () {
            throw Exception('Request timeout. Please check your internet connection and ensure the server at $base is accessible.');
          },
        );
      } catch (e) {
        if (e.toString().contains('SocketException') || e.toString().contains('Failed host lookup')) {
          throw Exception('Cannot connect to server. Please check:\n1. Your device is on the same network as the server\n2. The server IP address is correct: $base\n3. Apache/XAMPP is running on the server');
        } else if (e.toString().contains('timeout')) {
          throw Exception('Request timeout. The server may be slow or unreachable. Please check your network connection.');
        }
        rethrow;
      }
      
      if (!mounted) return; // Check again after async operation
      
      if (res.statusCode != 200) {
        throw Exception('Server returned status ${res.statusCode}');
      }
      
      final data = jsonDecode(res.body) as Map<String, dynamic>;
      
      if (data['success'] == true) {
        if (!mounted) return;
        setState(() {
          _walletData = data;
          _isLoading = false;
          _errorMessage = null;
        });
      } else {
        final errorMsg = data['message'] ?? 'Failed to fetch wallet';
        // If user not found, suggest logging out and back in
        if (errorMsg.toLowerCase().contains('user not found')) {
          throw Exception('$errorMsg\n\nPlease try logging out and logging back in to refresh your session.');
        }
        throw Exception(errorMsg);
      }
    } catch (e) {
      if (!mounted) return; // Don't call setState if disposed
      print('Wallet fetch error: $e');
      setState(() {
        _isLoading = false;
        _walletData = null;
        _errorMessage = e.toString().replaceAll('Exception: ', '');
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Wallet')),
      body: RefreshIndicator(
        onRefresh: _fetchWalletData,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (_isLoading)
                const Center(child: CircularProgressIndicator())
              else if (_walletData == null)
                Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.error_outline, size: 64, color: Colors.red),
                      const SizedBox(height: 16),
                      Text(
                        'Failed to load wallet data',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.grey[800]),
                      ),
                      if (_errorMessage != null) ...[
                        const SizedBox(height: 8),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 32),
                          child: Text(
                            _errorMessage!,
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 14, color: Colors.grey[600]),
                          ),
                        ),
                      ],
                    ],
                  ),
                )
              else ...[
                _item(context, 'Available Balance', _walletData!['current_balance'] ?? 0, Colors.green),
                const SizedBox(height: 12),
                _item(context, 'Total Earned', _walletData!['total_earned'] ?? 0, Colors.blue),
                const SizedBox(height: 12),
                _item(context, 'Total Redeemed', _walletData!['total_redeemed'] ?? 0, Colors.orange),
                const SizedBox(height: 12),
                _item(context, 'Total Deposits', _walletData!['total_deposits'] ?? 0, Colors.purple),
                const SizedBox(height: 12),
                // Add member since date
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade50,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.grey.shade200),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.calendar_today, color: Colors.grey.shade600),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Member Since',
                            style: TextStyle(
                              color: Colors.grey.shade600,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _formatMemberSince(_walletData!['last_transaction_date']),
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Colors.indigo,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _item(BuildContext context, String title, int value, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: const Color(0xFFEAF3E8), borderRadius: BorderRadius.circular(16)),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(title, style: TextStyle(color: Colors.lightBlue.shade600, fontWeight: FontWeight.w700)),
          Text('$value', style: TextStyle(color: color, fontSize: 28, fontWeight: FontWeight.w800)),
        ],
      ),
    );
  }

  String _formatMemberSince(String? dateString) {
    if (dateString == null || dateString.isEmpty) {
      return 'September 2025';
    }
    
    try {
      final date = DateTime.parse(dateString);
      final months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ];
      return '${months[date.month - 1]} ${date.year}';
    } catch (e) {
      return 'September 2025';
    }
  }
}


