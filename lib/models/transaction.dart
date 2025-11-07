class TransactionItem {
  final String id;
  final DateTime date;
  final String description;
  final double amount; // negative for debit, positive for credit
  final String type; // e.g., "Wallet Load", "Recharge", "QR Pay"

  TransactionItem({
    required this.id,
    required this.date,
    required this.description,
    required this.amount,
    required this.type,
  });
}