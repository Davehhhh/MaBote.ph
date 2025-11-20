import 'package:flutter/material.dart';
import 'screens/splash_screen.dart';
import 'services/notification_service.dart';
import 'services/premium_theme_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize notification service
  await NotificationService().initialize();
  
  runApp(const MaBoteApp());
}

class MaBoteApp extends StatefulWidget {
  const MaBoteApp({super.key});

  @override
  State<MaBoteApp> createState() => _MaBoteAppState();
}

class _MaBoteAppState extends State<MaBoteApp> {
  bool _isDarkMode = false;

  @override
  void initState() {
    super.initState();
    _loadTheme();
    
    // Listen for app lifecycle changes to refresh theme
    WidgetsBinding.instance.addObserver(_AppLifecycleObserver(this));
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(_AppLifecycleObserver(this));
    super.dispose();
  }

  Future<void> _loadTheme() async {
    try {
      final isDark = await PremiumThemeService.isDarkMode();
      if (mounted) {
        setState(() {
          _isDarkMode = isDark;
        });
      }
    } catch (e) {
      print('Error loading theme: $e');
      // If there's an error loading theme, default to light mode
      if (mounted) {
        setState(() {
          _isDarkMode = false;
        });
      }
    }
  }

  void toggleTheme() async {
    final newTheme = !_isDarkMode;
    await PremiumThemeService.setDarkMode(newTheme);
    setState(() {
      _isDarkMode = newTheme;
    });
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'MaBote.ph',
      theme: PremiumThemeService.getLightTheme(),
      darkTheme: PremiumThemeService.getDarkTheme(),
      themeMode: _isDarkMode ? ThemeMode.dark : ThemeMode.light,
      home: SplashScreen(onThemeToggle: toggleTheme),
    );
  }
}

class _AppLifecycleObserver extends WidgetsBindingObserver {
  final _MaBoteAppState _appState;

  _AppLifecycleObserver(this._appState);

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      // Refresh theme when app resumes
      _appState._loadTheme();
    }
  }
}
