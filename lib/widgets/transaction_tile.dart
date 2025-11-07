import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/transaction.dart';

class TransactionTile extends StatelessWidget {
  final TransactionItem item;
  const TransactionTile({super.key, required this.item});

  @override
  Widget build(BuildContext context) {
    final isDebit = item.amount < 0;
    final amountStr = (isDebit ? '-' : '+') + '₹${item.amount.abs().toStringAsFixed(2)}';
    final dateStr = DateFormat('dd MMM, yyyy • hh:mm a').format(item.date);

    return Card(
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: (isDebit ? Colors.red : Colors.green).withOpacity(0.1),
          child: Icon(isDebit ? Icons.remove_circle : Icons.add_circle, color: isDebit ? Colors.red : Colors.green),
        ),
        title: Text(item.description, maxLines: 1, overflow: TextOverflow.ellipsis),
        subtitle: Text('${item.type} • $dateStr'),
        trailing: Text(
          amountStr,
          style: TextStyle(color: isDebit ? Colors.red : Colors.green, fontWeight: FontWeight.w600),
        ),
      ),
    );
  }
}