import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:megapay/main.dart';

void main() {
  testWidgets('MegaPay home screen renders feature tiles', (WidgetTester tester) async {
    await tester.pumpWidget(const MegaPayApp());
    expect(find.text('MegaPay'), findsOneWidget);

    // Scrollable feature list exists
    expect(find.byType(ListView), findsWidgets);

    // Check some feature tiles by title
    expect(find.text('User Registration'), findsWidgets);
    expect(find.text('Wallet Cashload'), findsWidgets);
    expect(find.text('Transaction History'), findsWidgets);
  });
}