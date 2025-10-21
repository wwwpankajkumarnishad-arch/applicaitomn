import 'dart:math';

import '../models/transaction.dart';

class MockApi {
  static final Random _rng = Random();

  static Future<bool> authenticate(String email, String password) async {
    await Future.delayed(const Duration(milliseconds: 500));
    return email.isNotEmpty && password.length >= 4;
  }

  static Future<double> getWalletBalance() async {
    await Future.delayed(const Duration(milliseconds: 400));
    return 500.0;
  }

  static Future<double> loadWallet(double amount) async {
    await Future.delayed(const Duration(milliseconds: 500));
    return amount;
  }

  static Future<TransactionItem> createTransaction({
    required String description,
    required double amount,
    required String type,
  }) async {
    await Future.delayed(const Duration(milliseconds: 300));
    return TransactionItem(
      id: 'tx_${_rng.nextInt(999999)}',
      date: DateTime.now(),
      description: description,
      amount: amount,
      type: type,
    );
  }

  static Future<List<String>> getOperators(String category) async {
    await Future.delayed(const Duration(milliseconds: 400));
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

  static Future<bool> processRecharge({
    required String operator,
    required String accountNumber,
    required double amount,
  }) async {
    await Future.delayed(const Duration(milliseconds: 700));
    return operator.isNotEmpty && accountNumber.isNotEmpty && amount > 0;
  }
}