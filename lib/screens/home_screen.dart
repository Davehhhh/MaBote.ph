import 'dart:async';
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
  // Global key to access dashboard state for refreshing
  final GlobalKey<_DashboardPageState> _dashboardKey = GlobalKey<_DashboardPageState>();

  @override
  void initState() {
    super.initState();
    _loadNotificationCount();
    _checkForNewTransactions();
    
    // Refresh notification count every 15 seconds for better responsiveness
    Timer.periodic(const Duration(seconds: 15), (timer) {
      if (mounted) {
        _loadNotificationCount();
      } else {
        timer.cancel();
      }
    });
  }

  Future<void> _loadNotificationCount() async {
    await _notificationCountService.fetchUnreadCount();
    if (mounted) setState(() {});
  }

  Future<void> _checkForNewTransactions() async {
    try {
      final uid = await Session.userId();
      if (uid == null) return;

      const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
      final url = Uri.parse('$base/transactions.php?user_id=$uid');
      final res = await http.get(url);
      final data = jsonDecode(res.body);

      if (res.statusCode == 200 && data is Map<String, dynamic> && data['success'] == true) {
        final transactions = data['transactions'] as List;
        
        // Check if there are new transactions (created in the last 5 minutes)
        final now = DateTime.now();
        final fiveMinutesAgo = now.subtract(const Duration(minutes: 5));
        
        for (final transaction in transactions) {
          final transactionDate = DateTime.parse(transaction['transaction_date']);
          if (transactionDate.isAfter(fiveMinutesAgo) && transaction['transaction_type'] == 'deposit') {
            // Create notification for this new transaction
            await _createTransactionNotification(transaction);
          }
        }
      }
    } catch (e) {
      print('Error checking for new transactions: $e');
    }
  }

  Future<void> _createTransactionNotification(Map<String, dynamic> transaction) async {
    try {
      final uid = await Session.userId();
      if (uid == null) return;

      const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
      final url = Uri.parse('$base/send_notification.php');
      
      await http.post(
        url,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'user_id': uid,
          'notification_type': 'points',
          'title': '🎉 Bottle Deposit Successful!',
          'message': 'You deposited ${transaction['bottle_deposited']} bottle(s) and earned ${transaction['points_earned']} points!',
        }),
      );
    } catch (e) {
      print('Error creating transaction notification: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    final pages = <Widget>[
      const _ProfilePage(),
      _DashboardPage(
        key: _dashboardKey,
        onRefreshRequested: () {
          if (_dashboardKey.currentState != null) {
            _dashboardKey.currentState!._fetchWalletData();
          }
        },
      ),
      _SettingsPage(onThemeToggle: widget.onThemeToggle),
    ];
    
    return Scaffold(
      appBar: _index == 1 ? _buildAppBar() : null,
      body: SafeArea(child: IndexedStack(
        index: _index,
        children: pages,
      )),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) {
          setState(() => _index = i);
          // Refresh dashboard when switching to home tab
          if (i == 1 && _dashboardKey.currentState != null) {
            _dashboardKey.currentState!._fetchWalletData();
          }
        },
        destinations: const [
          NavigationDestination(icon: Icon(Icons.person_outline), label: 'Profile'),
          NavigationDestination(icon: Icon(Icons.home_outlined), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.settings_outlined), label: 'Settings'),
        ],
      ),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    return AppBar(
      title: Row(
        children: [
          Icon(Icons.recycling, color: Theme.of(context).colorScheme.primary),
          const SizedBox(width: 8),
          const Text('MaBote.ph'),
        ],
      ),
        actions: [
        Stack(
          children: [
            GestureDetector(
              onLongPress: () {
                _loadNotificationCount();
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Notification count refreshed'),
                    duration: Duration(seconds: 2),
                  ),
                );
              },
              child: IconButton(
                icon: const Icon(Icons.notifications_outlined),
                onPressed: () async {
                  await Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const NotificationsScreen()));
                  // Refresh notification count when returning
                  _loadNotificationCount();
                },
              ),
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
      ],
    );
  }
}

class _DashboardPage extends StatefulWidget {
  final VoidCallback? onRefreshRequested;
  
  const _DashboardPage({Key? key, this.onRefreshRequested}) : super(key: key);

  @override
  State<_DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<_DashboardPage> with AutomaticKeepAliveClientMixin {
  Map<String, dynamic>? _walletData;
  bool _isLoading = true;
  Timer? _refreshTimer;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _fetchWalletData();
    
    // Refresh wallet data every 5 seconds to show updated points
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

  // Make this public so parent can call it
  Future<void> _fetchWalletData() async {
    try {
      final uid = await Session.userId();
      if (uid == null) throw Exception('Not logged in - user_id is null');
      
      const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
      final url = Uri.parse('$base/get_wallet.php?user_id=$uid');
      final res = await http.get(url);
      final data = jsonDecode(res.body);
      
      if (res.statusCode == 200 && data is Map<String, dynamic> && data['success'] == true) {
        setState(() {
          _walletData = data;
          _isLoading = false;
        });
      } else {
        throw Exception(data['message'] ?? 'Failed to fetch wallet data');
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context); // Required for AutomaticKeepAliveClientMixin
    return RefreshIndicator(
      onRefresh: _fetchWalletData,
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Simple Welcome Text (like other modern apps)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: Row(
              children: [
                Icon(
                  Icons.waving_hand,
                  color: Theme.of(context).primaryColor,
                  size: 24,
                ),
                const SizedBox(width: 12),
                Text(
                  'Welcome back!',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).primaryColor,
                  ),
                ),
              ],
            ),
          ),
          
          const SizedBox(height: 20),
          
          // Professional Wallet Balance Card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [
                  Theme.of(context).primaryColor,
                  Theme.of(context).primaryColor.withOpacity(0.9),
                ],
              ),
              borderRadius: BorderRadius.circular(18),
              boxShadow: [
                BoxShadow(
                  color: Theme.of(context).primaryColor.withOpacity(0.25),
                  blurRadius: 20,
                  offset: const Offset(0, 8),
                ),
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(
                        Icons.account_balance_wallet,
                        color: Colors.white,
                        size: 24,
                      ),
                    ),
                    const SizedBox(width: 16),
                    Text(
                      'Available Balance',
                      style: TextStyle(
                        color: Colors.white.withOpacity(0.9),
                        fontSize: 18,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                if (_isLoading)
                  const SizedBox(
                    height: 50,
                    child: Center(
                      child: CircularProgressIndicator(
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                      ),
                    ),
                  )
                else
                  Text(
                    '${_walletData?['current_balance'] ?? 0} pts',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 36,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                const SizedBox(height: 20),
                Row(
                  children: [
                    Expanded(
                      child: _buildStatItem(
                        'Total Earned',
                        '${_walletData?['total_earned'] ?? 0}',
                        Icons.trending_up,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: _buildStatItem(
                        'Total Redeemed',
                        '${_walletData?['total_redeemed'] ?? 0}',
                        Icons.card_giftcard,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          
          const SizedBox(height: 24),
          
          // Quick Actions
          Text(
            'Quick Actions',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 16),
          
          // Professional Action Grid (Scrollable)
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 2,
            childAspectRatio: 0.85, // Proper ratio to prevent overflow
            crossAxisSpacing: 16,
            mainAxisSpacing: 16,
            children: [
              _buildActionCard(
                icon: Icons.qr_code_2,
                title: 'Account QR',
                subtitle: 'Show to machines',
                color: Colors.blue,
                onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const _AccountQrPage())),
              ),
              _buildActionCard(
                icon: Icons.account_balance_wallet_outlined,
                title: 'My Wallet',
                subtitle: 'View balance',
                color: Colors.green,
                onTap: () async {
                  await Navigator.of(context).push(MaterialPageRoute(builder: (_) => const WalletScreen()));
                  // Refresh wallet data when returning from wallet screen
                  if (mounted) {
                    _fetchWalletData();
                  }
                },
              ),
              _buildActionCard(
                icon: Icons.emoji_events_outlined,
                title: 'Rewards',
                subtitle: 'Claim prizes',
                color: Colors.orange,
                onTap: () async {
                  await Navigator.of(context).push(MaterialPageRoute(builder: (_) => const RewardsScreen()));
                  // Refresh wallet data when returning from rewards screen (in case reward was claimed)
                  if (mounted) {
                    _fetchWalletData();
                  }
                },
              ),
              _buildActionCard(
                icon: Icons.receipt_long_outlined,
                title: 'Transactions',
                subtitle: 'View history',
                color: Colors.purple,
                onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const TransactionsScreen())),
              ),
              _buildActionCard(
                icon: Icons.leaderboard_outlined,
                title: 'Leaderboard',
                subtitle: 'See rankings',
                color: Colors.red,
                onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LeaderboardScreen())),
              ),
              _buildActionCard(
                icon: Icons.analytics_outlined,
                title: 'Analytics',
                subtitle: 'View insights',
                color: Colors.teal,
                onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const AnalyticsScreen())),
              ),
              _buildActionCard(
                icon: Icons.location_on_outlined,
                title: 'Find Machines',
                subtitle: 'Locate nearby',
                color: Colors.indigo,
                onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const MachineFinderScreen())),
              ),
              _buildActionCard(
                icon: Icons.eco_outlined,
                title: 'Eco Tips',
                subtitle: 'Learn more',
                color: Colors.lightGreen,
                onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const EcoTipsScreen())),
              ),
            ],
          ),
          
          const SizedBox(height: 20),
        ],
      ),
      ),
    );
  }

  Widget _buildStatItem(String label, String value, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.15),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: Colors.white.withOpacity(0.2),
          width: 1,
        ),
      ),
      child: Column(
        children: [
          Icon(
            icon,
            color: Colors.white,
            size: 20,
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 16,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              color: Colors.white.withOpacity(0.8),
              fontSize: 12,
              fontWeight: FontWeight.w500,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildActionCard({
    required IconData icon,
    required String title,
    required String subtitle,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                Colors.white,
                Colors.grey.shade50,
              ],
            ),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: color.withOpacity(0.15),
              width: 1,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.05),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
              BoxShadow(
                color: color.withOpacity(0.08),
                blurRadius: 20,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      color,
                      color.withOpacity(0.8),
                    ],
                  ),
                  borderRadius: BorderRadius.circular(14),
                  boxShadow: [
                    BoxShadow(
                      color: color.withOpacity(0.3),
                      blurRadius: 8,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Icon(
                  icon,
                  color: Colors.white,
                  size: 26,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                title,
                style: const TextStyle(
                  color: Colors.black87,
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                style: TextStyle(
                  color: Colors.grey.shade600,
                  fontSize: 12,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AccountQrPage extends StatefulWidget {
  const _AccountQrPage();

  @override
  State<_AccountQrPage> createState() => _AccountQrPageState();
}

class _AccountQrPageState extends State<_AccountQrPage> {
  String? _qrData;
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _loadQrCode();
  }

  Future<void> _loadQrCode() async {
    try {
      // First try to get qr_id from session
      String? qrId = await Session.qrId();
      
      // If qr_id is not in session, fetch it from the API
      if (qrId == null || qrId.isEmpty) {
        final uid = await Session.userId();
        if (uid != null) {
          const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
          final url = Uri.parse('$base/get_profile.php?user_id=$uid');
          
          final response = await http.get(url).timeout(
            const Duration(seconds: 10),
            onTimeout: () {
              throw Exception('Connection timeout');
            },
          );
          
          if (response.statusCode == 200) {
            final data = jsonDecode(response.body) as Map<String, dynamic>;
            if (data['success'] == true && data['qr_id'] != null) {
              qrId = data['qr_id'].toString();
              // Save it to session for future use
              await Session.save(
                userId: uid,
                userName: await Session.userName() ?? 'User',
                userEmail: await Session.userEmail() ?? '',
                token: await Session.token() ?? '',
                qrId: qrId,
              );
            }
          }
        }
      }
      
      if (qrId != null && qrId.isNotEmpty) {
        setState(() {
          _qrData = qrId;
          _isLoading = false;
        });
      } else {
        throw Exception('QR code not available. Please log out and log in again.');
      }
    } catch (e) {
      setState(() {
        _errorMessage = e.toString().replaceAll('Exception: ', '');
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    
    return Scaffold(
      appBar: AppBar(
        title: const Text('Account QR'),
        backgroundColor: Theme.of(context).primaryColor,
        foregroundColor: Colors.white,
      ),
      body: _isLoading
          ? const Center(child: ModernLoading(message: 'Loading QR Code...'))
          : _errorMessage != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24.0),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.error_outline,
                          size: 64,
                          color: Colors.red.shade300,
                        ),
                        const SizedBox(height: 16),
                        Text(
                          'Error Loading QR Code',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey.shade700,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          _errorMessage!,
                          textAlign: TextAlign.center,
                          style: TextStyle(color: Colors.grey.shade600),
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton.icon(
                          onPressed: _loadQrCode,
                          icon: const Icon(Icons.refresh),
                          label: const Text('Retry'),
                        ),
                      ],
                    ),
                  ),
                )
              : _qrData == null
                  ? const Center(child: Text('QR code not available'))
                  : SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                const SizedBox(height: 20),
                // QR Code Display
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: Colors.white,
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
                          color: Colors.grey.shade50,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.grey.shade200),
                        ),
                        child: QrImageView(
                          data: _qrData!, 
                          version: QrVersions.auto, 
                          size: 200,
                          backgroundColor: Colors.white,
                          foregroundColor: Colors.black,
                        ),
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'Your Account QR Code',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Theme.of(context).primaryColor,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Show this to MaBote machines',
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 30),
                // Instructions
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Theme.of(context).cardColor,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(
                      color: Theme.of(context).brightness == Brightness.dark 
                        ? Colors.grey.shade700 
                        : Colors.grey.shade200,
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
          ),
    );
  }

  Widget _buildStep(String number, String title) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.green.withOpacity(0.05),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: Colors.green.withOpacity(0.2),
          ),
        ),
        child: Column(
          children: [
            Container(
              width: 32,
              height: 32,
              decoration: BoxDecoration(
                color: Colors.green,
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
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: Colors.green,
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
