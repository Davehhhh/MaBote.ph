import 'dart:convert';

import 'package:http/http.dart' as http;
import 'session.dart';

class AuthService {
  AuthService({String? baseUrl}) : _baseUrl = baseUrl ?? const String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');

  final String _baseUrl;

  Uri _url(String path) {
    final base = _baseUrl.endsWith('/') ? _baseUrl.substring(0, _baseUrl.length - 1) : _baseUrl;
    final p = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$base$p');
  }

  Future<Map<String, dynamic>> login({required String email, required String password, bool persist = true}) async {
    try {
      final response = await http.post(
        _url('/login.php'),
        headers: { 'Content-Type': 'application/json' },
        body: jsonEncode({ 'email': email, 'password': password }),
      ).timeout(
        const Duration(seconds: 10),
        onTimeout: () {
          throw AuthException(message: 'Connection timeout. Please check your internet connection and try again.', statusCode: 408);
        },
      );

      final data = _decode(response);
      if (response.statusCode == 200 && data['success'] == true) {
        if (persist) {
          await Session.save(
            userId: (data['user_id'] as num?)?.toInt() ?? 0,
            userName: data['name']?.toString() ?? 'User',
            userEmail: email,
            token: data['token']?.toString() ?? '',
            qrId: data['qr_id']?.toString(),
          );
        }
        return data;
      }
      throw AuthException(message: data['message']?.toString() ?? 'Login failed', statusCode: response.statusCode);
    } on AuthException {
      rethrow;
    } catch (e) {
      if (e.toString().contains('SocketException') || e.toString().contains('Failed host lookup')) {
        throw AuthException(message: 'Cannot connect to server. Please check your internet connection and ensure the API server is running.', statusCode: 0);
      } else if (e.toString().contains('timeout')) {
        throw AuthException(message: 'Connection timeout. Please try again.', statusCode: 408);
      }
      throw AuthException(message: 'Login failed: ${e.toString()}', statusCode: 0);
    }
  }

  Future<Map<String, dynamic>> signup({
    required String firstName,
    required String lastName,
    required String email,
    required String password,
    String? phone,
    String? address,
    String? barangay,
    String? city,
  }) async {
    try {
      final response = await http.post(
        _url('/signup_extended.php'),
        headers: { 'Content-Type': 'application/json' },
        body: jsonEncode({
          'first_name': firstName,
          'last_name': lastName,
          'email': email,
          'password': password,
          if (phone != null) 'phone': phone,
          if (address != null) 'address': address,
          if (barangay != null) 'barangay': barangay,
          if (city != null) 'city': city,
        }),
      ).timeout(
        const Duration(seconds: 10),
        onTimeout: () {
          throw AuthException(message: 'Connection timeout. Please check your internet connection and try again.', statusCode: 408);
        },
      );

      final data = _decode(response);
      if (response.statusCode == 200 && data['success'] == true) {
        // Extract user data from the 'data' field
        final userData = data['data'] as Map<String, dynamic>? ?? {};
        
        // Auto-save session for signup as well
        await Session.save(
          userId: (userData['user_id'] as num?)?.toInt() ?? 0,
          userName: userData['name']?.toString() ?? '$firstName $lastName',
          userEmail: email,
          token: userData['token']?.toString() ?? '',
          qrId: userData['qr_id']?.toString(),
        );
        return data;
      }
      throw AuthException(message: data['message']?.toString() ?? 'Signup failed', statusCode: response.statusCode);
    } on AuthException {
      rethrow;
    } catch (e) {
      if (e.toString().contains('SocketException') || e.toString().contains('Failed host lookup')) {
        throw AuthException(message: 'Cannot connect to server. Please check your internet connection and ensure the API server is running.', statusCode: 0);
      } else if (e.toString().contains('timeout')) {
        throw AuthException(message: 'Connection timeout. Please try again.', statusCode: 408);
      } else if (e.toString().contains('FormatException') || e.toString().contains('Invalid server response')) {
        throw AuthException(message: 'Server returned invalid response. Please check if the API endpoint is correct and the server is responding properly.', statusCode: 0);
      }
      throw AuthException(message: 'Signup failed: ${e.toString()}', statusCode: 0);
    }
  }

  Future<void> logout() async {
    await Session.clear();
  }

  Map<String, dynamic> _decode(http.Response response) {
    try {
      final body = response.body.trim();
      if (body.isEmpty) {
        return { 'success': false, 'message': 'Empty response from server', 'raw': '' };
      }
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      return { 'success': false, 'message': 'Invalid response format', 'raw': body };
    } catch (e) {
      // Log the actual error for debugging
      final bodyPreview = response.body.length > 200 
          ? '${response.body.substring(0, 200)}...' 
          : response.body;
      return { 
        'success': false, 
        'message': 'Invalid server response: ${e.toString()}', 
        'raw': bodyPreview,
        'statusCode': response.statusCode,
      };
    }
  }
}

class AuthException implements Exception {
  AuthException({required this.message, this.statusCode});
  final String message;
  final int? statusCode;
  @override
  String toString() => 'AuthException($statusCode): $message';
}


