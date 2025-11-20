import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../services/session.dart';

class AnalyticsScreen extends StatefulWidget {
  const AnalyticsScreen({super.key});

  @override
  State<AnalyticsScreen> createState() => _AnalyticsScreenState();
}

class _AnalyticsScreenState extends State<AnalyticsScreen> {
  Map<String, dynamic>? _analyticsData;
  bool _isLoading = true;
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    _fetchAnalytics();
    
    // Refresh analytics every 2 seconds for real-time updates
    _refreshTimer = Timer.periodic(const Duration(seconds: 2), (timer) {
      if (mounted) {
        _fetchAnalytics();
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

  Future<void> _fetchAnalytics() async {
    try {
      final uid = await Session.userId();
      if (uid == null) throw Exception('Not logged in');
      
      const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
      final url = Uri.parse('$base/get_wallet.php?user_id=$uid');
      final res = await http.get(url);
      final data = jsonDecode(res.body) as Map<String, dynamic>;
      
      if (res.statusCode == 200 && data['success'] == true) {
        setState(() {
          _analyticsData = data;
          _isLoading = false;
        });
      } else {
        throw Exception(data['message'] ?? 'Failed to fetch analytics');
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
      appBar: AppBar(
        title: const Text('Analytics'),
        backgroundColor: Colors.green.shade600,
        foregroundColor: Colors.white,
      ),
      body: RefreshIndicator(
        onRefresh: _fetchAnalytics,
        child: _isLoading
            ? const Center(child: CircularProgressIndicator())
            : _analyticsData == null
                ? const Center(child: Text('Failed to load analytics'))
                : SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        // Environmental Impact Section
                        _buildSection(
                          'Environmental Impact',
                          Icons.eco,
                          Colors.green,
                          [
                            _buildImpactCard('CO₂ Saved', '${((_analyticsData!['total_deposits'] ?? 0) * 0.5).toStringAsFixed(1)} kg', Icons.cloud, Colors.blue),
                            _buildImpactCard('Plastic Recycled', '${((_analyticsData!['total_deposits'] ?? 0) * 25).toStringAsFixed(0)}g', Icons.recycling, Colors.green),
                            _buildImpactCard('Bottles Recycled', '${_analyticsData!['total_deposits'] ?? 0}', Icons.local_drink, Colors.orange),
                          ],
                        ),
                        const SizedBox(height: 24),
                        
                        // Achievement Section
                        _buildSection(
                          'Achievements',
                          Icons.emoji_events,
                          Colors.amber,
                          [
                            _buildAchievementCard('Eco Warrior', (_analyticsData!['total_deposits'] ?? 0) >= 50, 'Recycle 50 bottles'),
                            _buildAchievementCard('Planet Saver', (_analyticsData!['total_deposits'] ?? 0) >= 100, 'Recycle 100 bottles'),
                            _buildAchievementCard('Green Champion', (_analyticsData!['total_deposits'] ?? 0) >= 200, 'Recycle 200 bottles'),
                          ],
                        ),
                        const SizedBox(height: 24),
                        
                        // Statistics Summary
                        Card(
                          elevation: 4,
                          child: Padding(
                            padding: const EdgeInsets.all(20),
                            child: Column(
                              children: [
                                Row(
                                  children: [
                                    Icon(Icons.analytics, color: Colors.indigo),
                                    const SizedBox(width: 12),
                                    Text(
                                      'Your Impact Summary',
                                      style: TextStyle(
                                        fontSize: 18,
                                        fontWeight: FontWeight.bold,
                                        color: Colors.indigo,
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 16),
                                Text(
                                  'By recycling ${_analyticsData!['total_deposits'] ?? 0} bottles, you\'ve helped save ${((_analyticsData!['total_deposits'] ?? 0) * 0.5).toStringAsFixed(1)} kg of CO₂ from entering the atmosphere. That\'s equivalent to planting ${((_analyticsData!['total_deposits'] ?? 0) * 0.1).toInt()} trees!',
                                  style: TextStyle(
                                    fontSize: 16,
                                    color: Colors.grey.shade700,
                                    height: 1.5,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
      ),
    );
  }

  Widget _buildSection(String title, IconData icon, Color color, List<Widget> children) {
    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: color, size: 28),
                const SizedBox(width: 12),
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _buildImpactCard(String title, String value, IconData icon, Color color) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withOpacity(0.2),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: color, size: 24),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: color,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAchievementCard(String title, bool isUnlocked, String description) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isUnlocked ? Colors.amber.withOpacity(0.1) : Colors.grey.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isUnlocked ? Colors.amber.withOpacity(0.3) : Colors.grey.withOpacity(0.3),
        ),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: isUnlocked ? Colors.amber.withOpacity(0.2) : Colors.grey.withOpacity(0.2),
              shape: BoxShape.circle,
            ),
            child: Icon(
              isUnlocked ? Icons.emoji_events : Icons.lock,
              color: isUnlocked ? Colors.amber : Colors.grey,
              size: 24,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: isUnlocked ? Colors.amber.shade700 : Colors.grey.shade600,
                  ),
                ),
                Text(
                  description,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                  ),
                ),
              ],
            ),
          ),
          if (isUnlocked)
            Icon(Icons.check_circle, color: Colors.green, size: 20),
        ],
      ),
    );
  }
}
