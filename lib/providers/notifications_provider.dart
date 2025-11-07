import 'package:flutter/foundation.dart';

class NotificationItem {
  final String title;
  final String body;
  final DateTime date;

  NotificationItem(this.title, this.body, this.date);
}

class NotificationsProvider extends ChangeNotifier {
  final List<NotificationItem> _items = [];

  List<NotificationItem> get items => List.unmodifiable(_items);

  void add(String title, String body) {
    _items.insert(0, NotificationItem(title, body, DateTime.now()));
    notifyListeners();
  }
}