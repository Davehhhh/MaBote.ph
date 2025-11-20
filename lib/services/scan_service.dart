import 'dart:convert';

import 'package:http/http.dart' as http;

class ScanService {
  ScanService({String? baseUrl}) : _baseUrl = baseUrl ?? const String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.0.2.2/mabote_php_api');

  final String _baseUrl;
  Uri _url(String path) => Uri.parse('$_baseUrl$path');

  Future<ScanResult> claimDeposit({required int userId, required String qrCode}) async {
    final response = await http.post(
      _url('/scan.php'),
      headers: { 'Content-Type': 'application/json' },
      body: jsonEncode({ 'user_id': userId, 'qr_code': qrCode }),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode == 200 && data['success'] == true) {
      return ScanResult(
        newTotalPoints: (data['new_total_points'] ?? 0) as int,
        pointsAdded: (data['points_added'] ?? 0) as int,
      );
    }
    throw Exception(data['message'] ?? 'Scan failed');
  }
}

class ScanResult {
  final int newTotalPoints;
  final int pointsAdded;
  ScanResult({required this.newTotalPoints, required this.pointsAdded});
}


