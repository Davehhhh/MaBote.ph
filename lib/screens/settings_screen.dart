import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import 'profile_screen.dart';
import 'notifications_screen.dart';
import 'rewards_screen.dart';
import 'wallet_screen.dart';
import 'login_screen.dart';
import '../services/session.dart';
import '../services/notification_service.dart';
import '../widgets/modern_loading.dart';

import '../services/premium_theme_service.dart';

class SettingsScreen extends StatefulWidget {
  final VoidCallback? onThemeToggle;
  
  const SettingsScreen({super.key, this.onThemeToggle});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  final NotificationService _notificationService = NotificationService();
  Map<String, bool> _notificationPreferences = {};
  bool _isDarkMode = false;

  @override
  void initState() {
    super.initState();
    _loadNotificationPreferences();
    _loadThemeMode();
  }

  Future<void> _loadThemeMode() async {
    final isDark = await PremiumThemeService.isDarkMode();
    setState(() {
      _isDarkMode = isDark;
    });
  }

  Future<void> _toggleTheme() async {
    setState(() {
      _isDarkMode = !_isDarkMode;
    });
    await PremiumThemeService.setDarkMode(_isDarkMode);
    widget.onThemeToggle?.call();
  }

  Future<void> _loadNotificationPreferences() async {
    print('Loading notification preferences in settings screen');
    final preferences = _notificationService.getNotificationPreferences();
    print('Loaded preferences: $preferences');
    setState(() {
      _notificationPreferences = preferences;
    });
  }

  Future<void> _updateNotificationPreference(String key, bool value) async {
    print('Updating notification preference: $key = $value');
    setState(() {
      _notificationPreferences[key] = value;
    });

    switch (key) {
      case 'points_notifications':
        print('Setting points notifications to: $value');
        await _notificationService.setPointsNotifications(value);
        break;
      case 'reward_notifications':
        print('Setting reward notifications to: $value');
        await _notificationService.setRewardNotifications(value);
        break;
      case 'system_notifications':
        print('Setting system notifications to: $value');
        await _notificationService.setSystemNotifications(value);
        break;
    }
    
    // Reload preferences to ensure they're updated
    await _loadNotificationPreferences();
  }

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _section('Account', [
          _item(context, Icons.person_outline, 'Edit Profile', () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ProfileScreen()))),
          _item(context, Icons.lock_outline, 'Change Password', () => _showChangePassword(context)),
          _item(context, Icons.logout, 'Logout', () => _logout(context)),
        ]),
        const SizedBox(height: 16),
        _section('Notifications', [
          _item(context, Icons.notifications_outlined, 'View Notifications', () => Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationsScreen()))),
          _notificationToggle('Points Notifications', 'Get notified when you earn points', 'points_notifications'),
          _notificationToggle('Reward Notifications', 'Get notified when you claim rewards', 'reward_notifications'),
          _notificationToggle('System Notifications', 'Get app updates and announcements', 'system_notifications'),
        ]),
        const SizedBox(height: 16),
        _section('Rewards', [
          _item(context, Icons.star_border, 'Points Rules', () => _showPointsRules(context)),
          _item(context, Icons.card_giftcard, 'Rewards Catalog', () => Navigator.push(context, MaterialPageRoute(builder: (_) => const RewardsScreen()))),
        ]),
        const SizedBox(height: 16),
        _section('Wallet', [
          _item(context, Icons.account_balance_wallet_outlined, 'Wallet', () => Navigator.push(context, MaterialPageRoute(builder: (_) => const WalletScreen()))),
        ]),
        const SizedBox(height: 16),
        _section('Appearance', [
          ListTile(
            leading: Icon(
              _isDarkMode ? Icons.dark_mode : Icons.light_mode,
              color: _isDarkMode ? Colors.amber : Colors.blue,
            ),
            title: Text('Dark Mode', style: const TextStyle(fontWeight: FontWeight.w600)),
            subtitle: Text(_isDarkMode ? 'Dark theme enabled' : 'Light theme enabled', style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
            trailing: Switch(
              value: _isDarkMode,
              onChanged: (value) => _toggleTheme(),
              activeColor: Colors.green,
            ),
          ),
        ]),
        const SizedBox(height: 16),
        _section('About', [
          ListTile(
            leading: const Icon(Icons.recycling, color: Colors.green),
            title: const Text('MaBote.ph'),
            subtitle: const Text('IoT-based bottle collection and rewards'),
          ),
        ]),
      ],
    );
  }

  Widget _section(String title, List<Widget> items) {
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Theme.of(context).brightness == Brightness.dark 
              ? Colors.black26 
              : Colors.grey.withOpacity(0.1),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(24, 20, 24, 16),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(
                    _getSectionIcon(title),
                    color: Theme.of(context).primaryColor,
                    size: 20,
                  ),
                ),
                const SizedBox(width: 12),
                Text(
                  title,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.bold,
                    color: Theme.of(context).primaryColor,
                  ),
                ),
              ],
            ),
          ),
          ...items,
        ],
      ),
    );
  }

  IconData _getSectionIcon(String title) {
    switch (title) {
      case 'Account':
        return Icons.person_outline;
      case 'Notifications':
        return Icons.notifications;
      case 'Rewards':
        return Icons.star_outline;
      case 'Wallet':
        return Icons.account_balance_wallet_outlined;
      case 'Appearance':
        return Icons.palette_outlined;
      case 'About':
        return Icons.info_outline;
      default:
        return Icons.settings_outlined;
    }
  }

  Widget _item(BuildContext context, IconData icon, String label, VoidCallback onTap) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(12),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: Theme.of(context).primaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Icon(
                    icon,
                    color: Theme.of(context).primaryColor,
                    size: 18,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Text(
                    label,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                Icon(
                  Icons.arrow_forward_ios,
                  color: Colors.grey.shade400,
                  size: 16,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _notificationToggle(String title, String subtitle, String key) {
    return ListTile(
      leading: Icon(
        _notificationPreferences[key] ?? true ? Icons.notifications_active : Icons.notifications_off,
        color: _notificationPreferences[key] ?? true ? Colors.green : Colors.grey,
      ),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.w500)),
      subtitle: Text(subtitle, style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
      trailing: Switch(
        value: _notificationPreferences[key] ?? true,
        onChanged: (value) => _updateNotificationPreference(key, value),
        activeColor: Colors.green,
      ),
    );
  }

  Future<void> _logout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          ElevatedButton(onPressed: () => Navigator.pop(context, true), child: const Text('Logout')),
        ],
      ),
    );
    
    if (confirmed == true) {
      await Session.clear();
      if (context.mounted) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
      }
    }
  }

  void _showPointsRules(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            Icon(Icons.star, color: Colors.amber),
            const SizedBox(width: 8),
            const Text('Points Rules'),
          ],
        ),
        content: const SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('How to Earn Points:', style: TextStyle(fontWeight: FontWeight.bold)),
              SizedBox(height: 8),
              Text('• 5 points per plastic bottle deposited'),
              Text('• Scan your Account QR at any MaBote machine'),
              Text('• Points are added instantly to your wallet'),
              SizedBox(height: 16),
              Text('How to Use Points:', style: TextStyle(fontWeight: FontWeight.bold)),
              SizedBox(height: 8),
              Text('• Redeem points for rewards in the catalog'),
              Text('• Points never expire'),
              Text('• Transfer points to other users (coming soon)'),
              SizedBox(height: 16),
              Text('Tips:', style: TextStyle(fontWeight: FontWeight.bold)),
              SizedBox(height: 8),
              Text('• Keep your Account QR safe'),
              Text('• Check your transaction history regularly'),
              Text('• Join our leaderboard for extra rewards!'),
            ],
          ),
        ),
        actions: [
          ElevatedButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Got it!'),
          ),
        ],
      ),
    );
  }

  Future<void> _showChangePassword(BuildContext context) async {
    final oldPasswordController = TextEditingController();
    final newPasswordController = TextEditingController();
    final confirmPasswordController = TextEditingController();
    bool isLoading = false;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          title: const Text('Change Password'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: oldPasswordController,
                  decoration: const InputDecoration(
                    labelText: 'Current Password',
                    prefixIcon: Icon(Icons.lock),
                  ),
                  obscureText: true,
                  enabled: !isLoading,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: newPasswordController,
                  decoration: const InputDecoration(
                    labelText: 'New Password',
                    prefixIcon: Icon(Icons.lock_outline),
                  ),
                  obscureText: true,
                  enabled: !isLoading,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: confirmPasswordController,
                  decoration: const InputDecoration(
                    labelText: 'Confirm New Password',
                    prefixIcon: Icon(Icons.lock_outline),
                  ),
                  obscureText: true,
                  enabled: !isLoading,
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: isLoading ? null : () => Navigator.pop(context, false), 
              child: const Text('Cancel')
            ),
            ElevatedButton(
              onPressed: isLoading ? null : () async {
                if (newPasswordController.text != confirmPasswordController.text) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Passwords do not match')),
                  );
                  return;
                }
                if (newPasswordController.text.length < 6) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Password must be at least 6 characters')),
                  );
                  return;
                }
                
                setState(() => isLoading = true);
                
                try {
                  // Call change password API
                  const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
                  final url = Uri.parse('$base/change_password.php');
                  final response = await http.post(
                    url,
                    headers: {'Content-Type': 'application/json'},
                    body: jsonEncode({
                      'user_id': await Session.userId(),
                      'old_password': oldPasswordController.text,
                      'new_password': newPasswordController.text,
                    }),
                  );

                  final data = jsonDecode(response.body) as Map<String, dynamic>;
                  if (response.statusCode == 200 && data['success'] == true) {
                    Navigator.pop(context, true);
                    showDialog(
                      context: context,
                      builder: (context) => const ModernSuccessDialog(
                        title: 'Success!',
                        message: 'Your password has been changed successfully!',
                      ),
                    );
                  } else {
                    showDialog(
                      context: context,
                      builder: (context) => ModernErrorDialog(
                        title: 'Error',
                        message: data['message'] ?? 'Failed to change password',
                      ),
                    );
                  }
                } catch (e) {
                  showDialog(
                    context: context,
                    builder: (context) => ModernErrorDialog(
                      title: 'Error',
                      message: 'An error occurred: $e',
                    ),
                  );
                } finally {
                  setState(() => isLoading = false);
                }
              },
              child: isLoading 
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                      ),
                    )
                  : const Text('Change'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showNotificationPreferences(BuildContext context) async {
    await showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Notification Preferences'),
        content: const Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Configure your notification settings:', style: TextStyle(fontWeight: FontWeight.bold)),
            SizedBox(height: 16),
            Text('• Push notifications for new rewards'),
            Text('• Email notifications for transactions'),
            Text('• SMS alerts for account activity'),
            SizedBox(height: 16),
            Text('Note: Notification preferences will be implemented in a future update.', style: TextStyle(fontStyle: FontStyle.italic)),
          ],
        ),
        actions: [
          ElevatedButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }
}


