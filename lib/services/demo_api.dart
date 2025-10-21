import 'dart:convert';
import 'package:http/http.dart' as http;

/// Demo API service that uses public endpoints to simulate real network calls.
/// - Auth: reqres.in (returns token for specific demo credentials)
/// - Wallet and Recharge: httpbin.org (echo endpoints to simulate success)
class DemoApiService {
  static const _reqResBase = 'https://reqres.in/api';
  static const _httpBinBase = 'https://httpbin.org';

  /// Returns true if login successful using reqres demo API.
  /// Valid demo credentials per reqres:
  ///   email: "eve.holt@reqres.in"
  ///   password: "cityslicka"
  static Future<bool> login(String email, String password) async {
    try {
      final res = await http.post(
        Uri.parse('$_reqResBase/login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      );
      if (res.statusCode == 200) {
        final data = jsonDecode(res.body) as Map<String, dynamic>;
        return (data['token'] as String?) != null;
      }
      return false;
    } catch (_) {
      return false;
    }
  }

  /// Simulate getting wallet balance via httpbin (always returns fixed demo balance if network ok)
  static Future<double> getWalletBalance() async {
    try {
      final res = await http.get(Uri.parse('$_httpBinBase/anything?feature=wallet_balance'));
      if (res.statusCode == 200) {
        return 500.0;
      }
      return 500.0;
    } catch (_) {
      return 500.0;
    }
  }

  /// Simulate loading wallet via httpbin POST; returns the loaded amount if network ok
  static Future<double> loadWallet(double amount) async {
    try {
      final res = await http.post(
        Uri.parse('$_httpBinBase/post'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'action': 'wallet_load', 'amount': amount}),
      );
      if (res.statusCode == 200) {
        return amount;
      }
      return amount;
    } catch (_) {
      return amount;
    }
  }

  /// Returns a demo list of operators; makes a GET call to simulate network delay/success.
  static Future<List<String>> getOperators(String category) async {
    try {
      final res = await http.get(Uri.parse('$_httpBinBase/anything?feature=operators&category=$category'));
      if (res.statusCode == 200) {
        switch (category) {
          case 'Prepaid':
            return ['Airtel', 'Jio', 'Vi', 'BSNL'];
          case 'DTH':
            return ['Tata Play', 'Airtel DTH', 'Dish TV'];
          case 'FASTag':
            return ['HDFC FASTag', 'ICICI FASTag', 'Paytm FASTag'];
          default:
            return ['Generic'];
        }
      }
      return ['Generic'];
    } catch (_) {
      return ['Generic'];
    }
  }

  /// Simulate processing recharge via httpbin POST; returns true if network ok and payload logical.
  static Future<bool> processRecharge({
    required String operator,
    required String accountNumber,
    required double amount,
  }) async {
    try {
      final res = await http.post(
        Uri.parse('$_httpBinBase/post'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'action': 'recharge',
          'operator': operator,
          'account': accountNumber,
          'amount': amount,
        }),
      );
      return res.statusCode == 200 && operator.isNotEmpty && accountNumber.isNotEmpty && amount > 0;
    } catch (_) {
      return false;
    }
  }
}