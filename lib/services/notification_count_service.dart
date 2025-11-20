import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'session.dart';

class NotificationCountService {
  static final NotificationCountService _instance = NotificationCountService._internal();
  factory NotificationCountService() => _instance;
  NotificationCountService._internal();

  int _unreadCount = 0;

  int get unreadCount => _unreadCount;

  Future<void> fetchUnreadCount() async {
    try {
      final userId = await Session.userId();
      if (userId == null) {
        _unreadCount = 0;
        return;
      }

      const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
      final url = Uri.parse('$base/notification_count.php?user_id=$userId');
      
      final response = await http.get(url);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body) as Map<String, dynamic>;
        if (data['success'] == true) {
          _unreadCount = data['data']['unread_count'] ?? 0;
        }
      }
    } catch (e) {
      print('Failed to fetch notification count: $e');
      _unreadCount = 0;
    }
  }

  void incrementCount() {
    _unreadCount++;
  }

  void resetCount() {
    _unreadCount = 0;
  }
}
