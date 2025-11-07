import 'package:flutter/foundation.dart';
import '../services/mock_api.dart';
import '../services/demo_api.dart';
import '../models/transaction.dart';

class WalletProvider extends ChangeNotifier {
  double _balance = 0;
  final List<TransactionItem> _transactions = [];

  double get balance => _balance;
  List<TransactionItem> get transactions => List.unmodifiable(_transactions);

  Future<void> init() async {
    _balance = await DemoApiService.getWalletBalance();
    notifyListeners();
  }

  Future<void> load(double amount) async {
    final loaded = await DemoApiService.loadWallet(amount);
    _balance += loaded;
    final tx = await MockApi.createTransaction(
      description: 'Wallet load',
      amount: loaded,
      type: 'Wallet Load',
    );
    _transactions.insert(0, tx);
    notifyListeners();
  }

  Future<void> pay(double amount, String description, {String type = 'QR Pay'}) async {
    if (amount <= 0 || amount > _balance) return;
    _balance -= amount;
    final tx = await MockApi.createTransaction(
      description: description,
      amount: -amount,
      type: type,
    );
    _transactions.insert(0, tx);
    notifyListeners();
  }

  Future<void> addRecharge(String operator, String accountNumber, double amount) async {
    final ok = await DemoApiService.processRecharge(
      operator: operator,
      accountNumber: accountNumber,
      amount: amount,
    );
    if (!ok) return;
    _balance -= amount;
    final tx = await MockApi.createTransaction(
      description: '$operator recharge ($accountNumber)',
      amount: -amount,
      type: 'Recharge',
    );
    _transactions.insert(0, tx);
    notifyListeners();
  }
}