import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:fluttertoast/fluttertoast.dart';
import 'session.dart';
import 'notification_count_service.dart';

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  bool _isInitialized = false;

  // Notification preferences
  bool _pointsNotifications = true;
  bool _rewardNotifications = true;
  bool _systemNotifications = true;

  Future<void> initialize() async {
    if (_isInitialized) return;

    // Load notification preferences
    await _loadPreferences();

    _isInitialized = true;
  }

  Future<void> _loadPreferences() async {
    final prefs = await SharedPreferences.getInstance();
    _pointsNotifications = prefs.getBool('points_notifications') ?? true;
    _rewardNotifications = prefs.getBool('reward_notifications') ?? true;
    _systemNotifications = prefs.getBool('system_notifications') ?? true;
    
    print('Loaded notification preferences:');
    print('Points: $_pointsNotifications');
    print('Rewards: $_rewardNotifications');
    print('System: $_systemNotifications');
  }

  Future<void> _savePreferences() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('points_notifications', _pointsNotifications);
    await prefs.setBool('reward_notifications', _rewardNotifications);
    await prefs.setBool('system_notifications', _systemNotifications);
    
    print('Saved notification preferences:');
    print('Points: $_pointsNotifications');
    print('Rewards: $_rewardNotifications');
    print('System: $_systemNotifications');
  }

  // Show toast notification
  void _showToast(String message, {bool isError = false}) {
    Fluttertoast.showToast(
      msg: message,
      toastLength: Toast.LENGTH_LONG,
      gravity: ToastGravity.TOP,
      timeInSecForIosWeb: 3,
      backgroundColor: isError ? Colors.red : Colors.green,
      textColor: Colors.white,
      fontSize: 16.0,
    );
  }

  // Show points earned notification
  void showPointsEarnedNotification({
    required int points,
    required int bottles,
    required String transactionCode,
  }) {
    if (!_pointsNotifications) return;

    _showToast('🎉 You earned $points points from $bottles bottle(s)!');
    
    // Increment notification count
    NotificationCountService().incrementCount();
  }

  // Show reward claimed notification
  void showRewardClaimedNotification({
    required String rewardName,
    required int pointsUsed,
  }) {
    print('showRewardClaimedNotification called - _rewardNotifications: $_rewardNotifications');
    if (!_rewardNotifications) {
      print('Reward notifications disabled, skipping notification');
      return;
    }

    _showToast('🎁 You claimed "$rewardName" for $pointsUsed points!');
    
    // Increment notification count
    NotificationCountService().incrementCount();
  }

  // Show system notification
  void showSystemNotification({
    required String title,
    required String body,
  }) {
    if (!_systemNotifications) return;

    _showToast('$title: $body');
    
    // Increment notification count
    NotificationCountService().incrementCount();
  }

  // Send notification to server (for real-time notifications)
  Future<void> sendServerNotification({
    required int userId,
    required String type,
    required String title,
    required String message,
  }) async {
    try {
      const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
      final url = Uri.parse('$base/send_notification.php');
      
      await http.post(
        url,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'user_id': userId,
          'notification_type': type,
          'title': title,
          'message': message,
        }),
      );
    } catch (e) {
      print('Failed to send server notification: $e');
    }
  }

  // Notification preferences getters and setters
  bool get pointsNotifications => _pointsNotifications;
  bool get rewardNotifications => _rewardNotifications;
  bool get systemNotifications => _systemNotifications;

  Future<void> setPointsNotifications(bool enabled) async {
    _pointsNotifications = enabled;
    await _savePreferences();
  }

  Future<void> setRewardNotifications(bool enabled) async {
    _rewardNotifications = enabled;
    await _savePreferences();
  }

  Future<void> setSystemNotifications(bool enabled) async {
    _systemNotifications = enabled;
    await _savePreferences();
  }

  // Get all notification preferences
  Map<String, bool> getNotificationPreferences() {
    return {
      'points_notifications': _pointsNotifications,
      'reward_notifications': _rewardNotifications,
      'system_notifications': _systemNotifications,
    };
  }
}
