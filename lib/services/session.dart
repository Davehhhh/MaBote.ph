import 'package:shared_preferences/shared_preferences.dart';

class Session {
  static const _userIdKey = 'userId';
  static const _userNameKey = 'userName';
  static const _userEmailKey = 'userEmail';
  static const _tokenKey = 'token';
  static const _qrIdKey = 'qrId';

  static Future<void> save({
    required int userId,
    required String userName,
    required String userEmail,
    required String token,
    String? qrId,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_userIdKey, userId);
    await prefs.setString(_userNameKey, userName);
    await prefs.setString(_userEmailKey, userEmail);
    await prefs.setString(_tokenKey, token);
    if (qrId != null) {
      await prefs.setString(_qrIdKey, qrId);
    }
  }

  static Future<int?> userId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getInt(_userIdKey);
  }

  static Future<String?> userName() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_userNameKey);
  }

  static Future<String?> userEmail() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_userEmailKey);
  }

  static Future<String?> token() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  static Future<String?> qrId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_qrIdKey);
  }

  static Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_userIdKey);
    await prefs.remove(_userNameKey);
    await prefs.remove(_userEmailKey);
    await prefs.remove(_tokenKey);
    await prefs.remove(_qrIdKey);
  }

  // Debug method to check stored data
  static Future<Map<String, dynamic>> debug() async {
    final prefs = await SharedPreferences.getInstance();
    return {
      'userId': prefs.getInt(_userIdKey),
      'userName': prefs.getString(_userNameKey),
      'userEmail': prefs.getString(_userEmailKey),
      'token': prefs.getString(_tokenKey),
      'qrId': prefs.getString(_qrIdKey),
    };
  }
}


