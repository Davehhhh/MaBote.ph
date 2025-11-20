import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

class LeaderboardScreen extends StatefulWidget {
  const LeaderboardScreen({super.key});

  @override
  State<LeaderboardScreen> createState() => _LeaderboardScreenState();
}

class _LeaderboardScreenState extends State<LeaderboardScreen> with SingleTickerProviderStateMixin {
  Future<List<Map<String, dynamic>>>? _allTimeFuture;
  Future<List<Map<String, dynamic>>>? _monthlyFuture;
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _allTimeFuture = _fetch('all');
    _monthlyFuture = _fetch('monthly');
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<List<Map<String, dynamic>>> _fetch(String period) async {
    const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
    final url = Uri.parse('$base/leaderboard.php?period=$period');
    final res = await http.get(url);
    final data = jsonDecode(res.body) as Map<String, dynamic>;
    if (res.statusCode == 200 && data['success'] == true) {
      return (data['leaderboard'] as List).cast<Map<String, dynamic>>();
    }
    throw Exception(data['message'] ?? 'Failed to fetch leaderboard');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Leaderboard'),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(48),
          child: Container(
            color: Colors.transparent,
            child: TabBar(
              controller: _tabController,
              indicatorColor: Colors.white,
              labelColor: Colors.white,
              unselectedLabelColor: Colors.white.withOpacity(0.7),
              indicatorWeight: 3,
              indicatorPadding: const EdgeInsets.symmetric(horizontal: 20),
              labelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
              unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w400, fontSize: 14),
              tabs: const [
                Tab(
                  icon: Icon(Icons.emoji_events, size: 18),
                  text: 'All Time',
                  iconMargin: EdgeInsets.only(bottom: 2),
                ),
                Tab(
                  icon: Icon(Icons.calendar_month, size: 18),
                  text: 'This Month',
                  iconMargin: EdgeInsets.only(bottom: 2),
                ),
              ],
            ),
          ),
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildLeaderboard(_allTimeFuture, 'All Time'),
          _buildLeaderboard(_monthlyFuture, 'This Month'),
        ],
      ),
    );
  }

  Widget _buildLeaderboard(Future<List<Map<String, dynamic>>>? future, String period) {
    return FutureBuilder<List<Map<String, dynamic>>>(
      future: future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
                const SizedBox(height: 16),
                Text('Failed to load $period leaderboard'),
                const SizedBox(height: 8),
                Text(snapshot.error.toString()),
              ],
            ),
          );
        }
        final users = snapshot.data!;
        
        if (users.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.leaderboard_outlined, size: 64, color: Colors.grey.shade400),
                const SizedBox(height: 16),
                Text('No data for $period', style: TextStyle(fontSize: 18, color: Colors.grey.shade600)),
              ],
            ),
          );
        }
        
        return ListView.separated(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 100), // Increased bottom padding to fix overflow
          itemCount: users.length,
          separatorBuilder: (_, __) => const SizedBox(height: 8),
          itemBuilder: (context, i) {
            final user = users[i];
            final rank = i + 1;
            return Container(
              padding: const EdgeInsets.all(4), // Minimal padding
              decoration: BoxDecoration(
                color: rank <= 3 ? const Color(0xFFFFF3CD) : const Color(0xFFEAF3E8),
                borderRadius: BorderRadius.circular(16),
                border: rank <= 3 ? Border.all(color: Colors.amber.shade300, width: 2) : null,
              ),
              child: Row(
                children: [
                  Container(
                    width: 50,
                    height: 50,
                    decoration: BoxDecoration(
                      color: _getRankColor(rank),
                      shape: BoxShape.circle,
                    ),
                    child: Center(
                      child: rank <= 3 
                        ? Icon(_getRankIcon(rank), color: Colors.white, size: 24)
                        : Text('$rank', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18)),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          user['full_name'] ?? 'Unknown User',
                          style: TextStyle(
                            fontWeight: FontWeight.w700, 
                            fontSize: rank <= 3 ? 18 : 16,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${user['total_earned']} points earned',
                          style: TextStyle(color: Colors.grey[600], fontWeight: FontWeight.w600),
                        ),
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            Icon(Icons.recycling, size: 14, color: Colors.green),
                            const SizedBox(width: 4),
                            Text(
                              '${user['total_deposits']} deposits',
                              style: TextStyle(color: Colors.grey[500], fontSize: 12),
                            ),
                            const SizedBox(width: 12),
                            Icon(Icons.eco, size: 14, color: Colors.blue),
                            const SizedBox(width: 4),
                            Text(
                              '${user['total_bottles']} bottles',
                              style: TextStyle(color: Colors.grey[500], fontSize: 12),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        '${user['total_deposits']}',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: rank <= 3 ? 14 : 12,
                          color: _getRankColor(rank),
                        ),
                      ),
                      Text('deposits', style: TextStyle(color: Colors.grey[600], fontSize: 8)),
                    ],
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Color _getRankColor(int rank) {
    switch (rank) {
      case 1: return Colors.amber.shade600; // Gold
      case 2: return Colors.grey.shade500;  // Silver
      case 3: return Colors.orange.shade600; // Bronze
      default: return Colors.lightBlue;
    }
  }

  IconData _getRankIcon(int rank) {
    switch (rank) {
      case 1: return Icons.looks_one;
      case 2: return Icons.looks_two;
      case 3: return Icons.looks_3;
      default: return Icons.person;
    }
  }
}
