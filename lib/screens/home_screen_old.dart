import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:http/http.dart' as http;
import 'wallet_screen.dart';
import 'rewards_screen.dart';
import 'transactions_screen.dart';
import 'profile_screen.dart';
import 'leaderboard_screen.dart';
import 'notifications_screen.dart';
import 'settings_screen.dart';
import 'analytics_screen.dart';
import 'machine_finder_screen.dart';
import 'eco_tips_screen.dart';
import '../services/session.dart';
import '../services/notification_count_service.dart';
import '../widgets/modern_loading.dart';

class HomeScreen extends StatefulWidget {
  final VoidCallback? onThemeToggle;
  
  const HomeScreen({super.key, this.onThemeToggle});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _index = 1; // default to Home tab
  final NotificationCountService _notificationCountService = NotificationCountService();

  @override
  void initState() {
    super.initState();
    _loadNotificationCount();
  }

  Future<void> _loadNotificationCount() async {
    await _notificationCountService.fetchUnreadCount();
    if (mounted) setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final pages = <Widget>[
      const _ProfilePage(),
      const _DashboardPage(),
      _SettingsPage(onThemeToggle: widget.onThemeToggle),
    ];
    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            Icon(Icons.recycling, color: Theme.of(context).colorScheme.primary),
            const SizedBox(width: 8),
            const Text('MaBote.ph'),
          ],
        ),
        actions: _index == 1
            ? [
                Stack(
                  children: [
                    IconButton(
                      icon: const Icon(Icons.notifications_outlined),
                      onPressed: () async {
                        await Navigator.of(context).push(
                            MaterialPageRoute(builder: (_) => const NotificationsScreen()));
                        // Refresh notification count when returning
                        _loadNotificationCount();
                      },
                    ),
                    if (_notificationCountService.unreadCount > 0)
                      Positioned(
                        right: 8,
                        top: 8,
                        child: Container(
                          padding: const EdgeInsets.all(2),
                          decoration: BoxDecoration(
                            color: Colors.red,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          constraints: const BoxConstraints(
                            minWidth: 16,
                            minHeight: 16,
                          ),
                          child: Text(
                            _notificationCountService.unreadCount > 99 
                                ? '99+' 
                                : _notificationCountService.unreadCount.toString(),
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ),
                  ],
                ),
              ]
            : null,
      ),
      body: SafeArea(child: pages[_index]),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.person_outline), label: 'Profile'),
          NavigationDestination(icon: Icon(Icons.home_outlined), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.settings_outlined), label: 'Settings'),
        ],
      ),
    );
  }
}

class _DashboardPage extends StatelessWidget {
  const _DashboardPage();

  Future<Map<String, dynamic>> _fetchWalletData() async {
    final uid = await Session.userId();
    if (uid == null) throw Exception('Not logged in');
    const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
    final url = Uri.parse('$base/get_wallet.php?user_id=$uid');
    final res = await http.get(url);
    final data = jsonDecode(res.body) as Map<String, dynamic>;
    if (res.statusCode == 200 && data['success'] == true) return data['wallet'] as Map<String, dynamic>;
    throw Exception(data['message'] ?? 'Failed to fetch wallet');
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          FutureBuilder<String?>(
            future: Session.userName(),
            builder: (context, snapshot) {
              final fullName = snapshot.data ?? 'User';
              final firstName = fullName.split(' ').first;
              final capitalizedFirstName = firstName.isNotEmpty 
                  ? '${firstName[0].toUpperCase()}${firstName.substring(1).toLowerCase()}'
                  : 'User';
              return Text('Hello $capitalizedFirstName!', style: Theme.of(context).textTheme.headlineMedium?.copyWith(color: Colors.lightBlue.shade400, fontWeight: FontWeight.w800));
            },
          ),
          const SizedBox(height: 16),
          FutureBuilder<Map<String, dynamic>>(
            future: _fetchWalletData(),
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const _CardBox(
                  color: Color(0xFFD6ECFA),
                  child: Center(
                    child: ModernLoading(message: 'Loading wallet...'),
                  ),
                );
              }
              
              if (snapshot.hasError) {
                return _CardBox(
                  color: const Color(0xFFD6ECFA),
                  child: Column(
                    children: [
                      const Icon(Icons.error_outline, color: Colors.red, size: 40),
                      const SizedBox(height: 8),
                      Text(
                        'Unable to load wallet data',
                        style: TextStyle(color: Colors.lightBlue.shade600, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 8),
                      ElevatedButton(
                        onPressed: () {
                          // Trigger rebuild to retry
                          (context as Element).markNeedsBuild();
                        },
                        child: const Text('Retry'),
                      ),
                    ],
                  ),
                );
              }
              
              final currentBalance = snapshot.data?['current_balance'] ?? 0;
              final totalEarned = snapshot.data?['total_earned'] ?? 0;
              final totalRedeemed = snapshot.data?['total_redeemed'] ?? 0;
              
              return Column(
                children: [
                  // Current Balance Card
                  _CardBox(
                    color: const Color(0xFFD6ECFA),
                    child: Row(
                      children: [
                        const Icon(Icons.account_balance_wallet, color: Colors.blue, size: 40),
                        const SizedBox(width: 16),
                        Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text('Available Balance:', style: TextStyle(color: Colors.lightBlue.shade600, fontWeight: FontWeight.w700)),
                          const SizedBox(height: 6),
                          Text('$currentBalance pts', style: const TextStyle(color: Colors.green, fontSize: 32, fontWeight: FontWeight.w800)),
                        ])
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  // Stats Row
                  Row(
                    children: [
                      Expanded(
                        child: _StatCard(
                          icon: Icons.star,
                          label: 'Total Earned',
                          value: '$totalEarned',
                          color: Colors.orange,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _StatCard(
                          icon: Icons.redeem,
                          label: 'Total Redeemed',
                          value: '$totalRedeemed',
                          color: Colors.purple,
                        ),
                      ),
                    ],
                  ),
                ],
              );
            },
          ),
          const SizedBox(height: 16),
          _ActionTile(icon: Icons.qr_code_2, label: 'Account QR Code', onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const _AccountQrPage()))),
          const SizedBox(height: 12),
          _ActionTile(icon: Icons.account_balance_wallet_outlined, label: 'My Wallet', onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const WalletScreen()))),
          const SizedBox(height: 12),
          _ActionTile(icon: Icons.emoji_events_outlined, label: 'Rewards', onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const RewardsScreen()))),
          const SizedBox(height: 12),
          _ActionTile(icon: Icons.receipt_long_outlined, label: 'Transaction', onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const TransactionsScreen()))),
          const SizedBox(height: 12),
          _ActionTile(icon: Icons.leaderboard_outlined, label: 'Leaderboard', onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LeaderboardScreen()))),
          const SizedBox(height: 12),
          _ActionTile(icon: Icons.analytics_outlined, label: 'Analytics', onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const AnalyticsScreen()))),
          const SizedBox(height: 12),
          _ActionTile(icon: Icons.location_on_outlined, label: 'Find Machines', onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const MachineFinderScreen()))),
          const SizedBox(height: 12),
          _ActionTile(icon: Icons.eco_outlined, label: 'Eco Tips', onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const EcoTipsScreen()))),
          const SizedBox(height: 12),
        ],
      ),
    );
  }
}

class _AccountQrPage extends StatelessWidget {
  const _AccountQrPage();

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final Future<String> qrFuture = Session.userId().then((id) => 'UID:${id ?? 0}');
    
    return Scaffold(
      appBar: AppBar(
        title: const Text('Account QR'),
        backgroundColor: Theme.of(context).primaryColor,
        foregroundColor: Colors.white,
        elevation: 0,
      ),
      body: FutureBuilder<String>(
        future: qrFuture,
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: ModernLoading(message: 'Loading QR Code...'));
          }
          final qrData = snapshot.data!;
          return Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Theme.of(context).primaryColor,
                  Theme.of(context).scaffoldBackgroundColor,
                ],
                stops: const [0.0, 0.3],
              ),
            ),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  const SizedBox(height: 20),
                  // Header
                  Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.95),
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.1),
                          blurRadius: 20,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: Column(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Theme.of(context).primaryColor.withOpacity(0.1),
                            shape: BoxShape.circle,
                          ),
                          child: Icon(
                            Icons.qr_code_2,
                            size: 40,
                            color: Theme.of(context).primaryColor,
                          ),
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Your QR Code',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: Theme.of(context).primaryColor,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'Show this to MaBote machines',
                          style: TextStyle(
                            fontSize: 16,
                            color: Colors.grey.shade600,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 30),
                  // QR Code Display
                  Container(
                    padding: const EdgeInsets.all(32),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(24),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.15),
                          blurRadius: 30,
                          offset: const Offset(0, 15),
                        ),
                      ],
                    ),
                    child: Column(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            color: Colors.grey.shade50,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: Colors.grey.shade200),
                          ),
                          child: QrImageView(
                            data: qrData, 
                            version: QrVersions.auto, 
                            size: 200,
                            backgroundColor: Colors.white,
                            foregroundColor: Colors.black,
                          ),
                        ),
                        const SizedBox(height: 20),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.verified_user,
                              color: Colors.green.shade600,
                              size: 20,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              'Verified Account',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.green.shade600,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                const SizedBox(height: 30),
                // Points Info
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.green.shade50,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.green.shade200),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.star, color: Colors.green.shade600, size: 32),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Earn 5 Points Per Bottle',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: Colors.green.shade700,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Points are added instantly to your wallet',
                              style: TextStyle(
                                fontSize: 14,
                                color: Colors.green.shade600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                // Simple How It Works
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Theme.of(context).cardColor,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: isDark ? Colors.grey.shade700 : Colors.grey.shade200,
                    ),
                  ),
                  child: Column(
                    children: [
                      Text(
                        'How It Works',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          _buildStep('1', 'Scan QR'),
                          const SizedBox(width: 12),
                          _buildStep('2', 'Insert Bottle'),
                          const SizedBox(width: 12),
                          _buildStep('3', 'Earn Points'),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 30),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildStep(String number, String title) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Theme.of(context).primaryColor.withOpacity(0.05),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: Theme.of(context).primaryColor.withOpacity(0.2),
          ),
        ),
        child: Column(
          children: [
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: Theme.of(context).primaryColor,
                shape: BoxShape.circle,
              ),
              child: Center(
                child: Text(
                  number,
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 8),
            Text(
              title,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: Theme.of(context).primaryColor,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

class _ProfilePage extends StatelessWidget {
  const _ProfilePage();
  @override
  Widget build(BuildContext context) {
    return const ProfileScreen();
  }
}

class _SettingsPage extends StatelessWidget {
  final VoidCallback? onThemeToggle;
  
  const _SettingsPage({super.key, this.onThemeToggle});

  @override
  Widget build(BuildContext context) {
    return SettingsScreen(onThemeToggle: onThemeToggle);
  }
}

class _CardBox extends StatelessWidget {
  const _CardBox({required this.child, this.color});
  final Widget child;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color ?? const Color(0xFFEAF3E8),
        borderRadius: BorderRadius.circular(20),
      ),
      child: child,
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });
  
  final IconData icon;
  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.1),
            spreadRadius: 1,
            blurRadius: 3,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey.shade600,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

class _ActionTile extends StatelessWidget {
  const _ActionTile({required this.icon, required this.label, required this.onTap});
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
        decoration: BoxDecoration(color: const Color(0xFFEAF3E8), borderRadius: BorderRadius.circular(18)),
        child: Row(
          children: [
            Icon(icon, color: Colors.lightBlue.shade400, size: 28),
            const SizedBox(width: 16),
            Text(label, style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: Colors.lightBlue.shade600)),
          ],
        ),
      ),
    );
  }
}


