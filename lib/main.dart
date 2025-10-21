import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

import 'providers/wallet_provider.dart';
import 'providers/notifications_provider.dart';
import 'models/transaction.dart';
import 'services/mock_api.dart';
import 'widgets/capture_scaffold.dart';
import 'widgets/section.dart';
import 'widgets/primary_button.dart';
import 'theme/app_theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const MegaPayApp());
}

class MegaPayApp extends StatelessWidget {
  const MegaPayApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => WalletProvider()..init()),
        ChangeNotifierProvider(create: (_) => NotificationsProvider()),
      ],
      child: ScreenUtilInit(
        designSize: const Size(390, 844),
        minTextAdapt: true,
        builder: (context, child) {
          return MaterialApp(
            title: 'MegaPay',
            theme: AppTheme.theme(),
            home: const AppHome(),
            routes: {
              SignInScreen.route: (_) => const SignInScreen(),
              SignUpScreen.route: (_) => const SignUpScreen(),
              ResetPasswordScreen.route: (_) => const ResetPasswordScreen(),
              WalletScreen.route: (_) => const WalletScreen(),
              RechargeScreen.route: (_) => const RechargeScreen(),
              QrScanPayScreen.route: (_) => const QrScanPayScreen(),
              TransactionsScreen.route: (_) => const TransactionsScreen(),
              AccountStatementScreen.route: (_) => const AccountStatementScreen(),
              NotificationsScreen.route: (_) => const NotificationsScreen(),
              ServiceRequestScreen.route: (_) => const ServiceRequestScreen(),
            },
          );
        },
      ),
    );
  }
}

class AuthProvider extends ChangeNotifier {
  bool _signedIn = false;
  String? _email;

  bool get signedIn => _signedIn;
  String? get email => _email;

  Future<void> signIn(String email, String password) async {
    final ok = await MockApi.authenticate(email, password);
    if (!ok) return;
    _email = email;
    _signedIn = true;
    notifyListeners();
  }

  Future<void> signUp(String email, String password) async {
    final ok = await MockApi.authenticate(email, password);
    if (!ok) return;
    _email = email;
    _signedIn = true;
    notifyListeners();
  }

  Future<void> resetPassword(String email) async {
    await Future.delayed(const Duration(milliseconds: 400));
  }

  void signOut() {
    _email = null;
    _signedIn = false;
    notifyListeners();
  }
}

class AppHome extends StatelessWidget {
  const AppHome({super.key});

  @override
  Widget build(BuildContext context) {
    final signedIn = context.watch<AuthProvider>().signedIn;

    return CaptureScaffold(
      title: 'MegaPay',
      screenName: 'home',
      child: ListView(
        padding: EdgeInsets.all(16.w),
        children: [
          Card(
            child: Padding(
              padding: EdgeInsets.all(16.w),
              child: Row(
                children: [
                  Icon(Icons.account_balance_wallet, size: 32.sp, color: Theme.of(context).colorScheme.primary),
                  SizedBox(width: 12.w),
                  Expanded(
                    child: Text(
                      'Welcome to MegaPay',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
                    ),
                  ),
                  PrimaryButton(
                    label: signedIn ? 'Sign Out' : 'Sign In',
                    icon: signedIn ? Icons.logout : Icons.login,
                    onPressed: () {
                      if (signedIn) {
                        context.read<AuthProvider>().signOut();
                      } else {
                        Navigator.pushNamed(context, SignInScreen.route);
                      }
                    },
                  ),
                ],
              ),
            ),
          ),
          SizedBox(height: 8.h),
          const Section(
            title: 'Account',
            icon: Icons.person,
            child: Column(
              children: [
                FeatureTile('User Registration', SignUpScreen.route, Icons.person_add),
                FeatureTile('User Sign In', SignInScreen.route, Icons.login),
                FeatureTile('Reset Password', ResetPasswordScreen.route, Icons.lock_reset),
              ],
            ),
          ),
          const Section(
            title: 'Payments & Services',
            icon: Icons.bolt,
            child: Column(
              children: [
                FeatureTile('Wallet Cashload', WalletScreen.route, Icons.account_balance_wallet),
                FeatureTile('Recharge Services', RechargeScreen.route, Icons.bolt),
                FeatureTile('QR Scan & Pay', QrScanPayScreen.route, Icons.qr_code_scanner),
              ],
            ),
          ),
          const Section(
            title: 'Records',
            icon: Icons.list_alt,
            child: Column(
              children: [
                FeatureTile('Transaction History', TransactionsScreen.route, Icons.receipt_long),
                FeatureTile('Account Statement', AccountStatementScreen.route, Icons.description_outlined),
              ],
            ),
          ),
          const Section(
            title: 'Support & Notifications',
            icon: Icons.notifications,
            child: Column(
              children: [
                FeatureTile('Push Notification', NotificationsScreen.route, Icons.notifications),
                FeatureTile('Service Request', ServiceRequestScreen.route, Icons.support_agent),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

import 'widgets/feature_card.dart';

class FeatureTile extends StatelessWidget {
  final String title;
  final String route;
  final IconData icon;
  const FeatureTile(this.title, this.route, this.icon, {super.key});

  @override
  Widget build(BuildContext context) {
    return FeatureCard(title: title, route: route, icon: icon);
  }
}

// Screens

class SignInScreen extends StatefulWidget {
  static const route = '/signin';
  const SignInScreen({super.key});

  @override
  State<SignInScreen> createState() => _SignInScreenState();
}

class _SignInScreenState extends State<SignInScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _loading = false;

  @override
  Widget build(BuildContext context) {
    return CaptureScaffold(
      title: 'Sign In',
      screenName: 'sign_in',
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(controller: _emailController, decoration: const InputDecoration(labelText: 'Email')),
            const SizedBox(height: 12),
            TextField(controller: _passwordController, decoration: const InputDecoration(labelText: 'Password'), obscureText: true),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _loading
                  ? null
                  : () async {
                      setState(() => _loading = true);
                      await context.read<AuthProvider>().signIn(_emailController.text, _passwordController.text);
                      if (mounted) Navigator.pop(context);
                      setState(() => _loading = false);
                    },
              child: _loading ? const CircularProgressIndicator() : const Text('Sign In'),
            ),
          ],
        ),
      ),
    );
  }
}

class SignUpScreen extends StatefulWidget {
  static const route = '/signup';
  const SignUpScreen({super.key});

  @override
  State<SignUpScreen> createState() => _SignUpScreenState();
}

class _SignUpScreenState extends State<SignUpScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _loading = false;

  @override
  Widget build(BuildContext context) {
    return CaptureScaffold(
      title: 'Sign Up',
      screenName: 'sign_up',
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(controller: _emailController, decoration: const InputDecoration(labelText: 'Email')),
            const SizedBox(height: 12),
            TextField(controller: _passwordController, decoration: const InputDecoration(labelText: 'Password'), obscureText: true),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _loading
                  ? null
                  : () async {
                      setState(() => _loading = true);
                      await context.read<AuthProvider>().signUp(_emailController.text, _passwordController.text);
                      if (mounted) Navigator.pop(context);
                      setState(() => _loading = false);
                    },
              child: _loading ? const CircularProgressIndicator() : const Text('Create Account'),
            ),
          ],
        ),
      ),
    );
  }
}

class ResetPasswordScreen extends StatefulWidget {
  static const route = '/reset-password';
  const ResetPasswordScreen({super.key});

  @override
  State<ResetPasswordScreen> createState() => _ResetPasswordScreenState();
}

class _ResetPasswordScreenState extends State<ResetPasswordScreen> {
  final _emailController = TextEditingController();
  bool _loading = false;

  @override
  Widget build(BuildContext context) {
    return CaptureScaffold(
      title: 'Reset Password',
      screenName: 'reset_password',
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(controller: _emailController, decoration: const InputDecoration(labelText: 'Email')),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _loading
                  ? null
                  : () async {
                      setState(() => _loading = true);
                      await context.read<AuthProvider>().resetPassword(_emailController.text);
                      if (mounted) Navigator.pop(context);
                      setState(() => _loading = false);
                    },
              child: _loading ? const CircularProgressIndicator() : const Text('Send Reset Link'),
            ),
          ],
        ),
      ),
    );
  }
}

class WalletScreen extends StatefulWidget {
  static const route = '/wallet';
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  final _amountController = TextEditingController(text: '100');

  @override
  Widget build(BuildContext context) {
    final wallet = context.watch<WalletProvider>();
    return CaptureScaffold(
      title: 'Wallet',
      screenName: 'wallet',
      child: Padding(
        padding: EdgeInsets.all(16.w),
        child: Column(
          children: [
            Card(
              child: Padding(
                padding: EdgeInsets.all(16.w),
                child: Row(
                  children: [
                    Icon(Icons.account_balance_wallet, size: 32.sp, color: Theme.of(context).colorScheme.primary),
                    SizedBox(width: 12.w),
                    Expanded(
                      child: Text(
                        'Balance: ₹${wallet.balance.toStringAsFixed(2)}',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            SizedBox(height: 12.h),
            Card(
              child: Padding(
                padding: EdgeInsets.all(16.w),
                child: Column(
                  children: [
                    TextField(
                      controller: _amountController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(labelText: 'Amount to load'),
                    ),
                    SizedBox(height: 12.h),
                    PrimaryButton(
                      label: 'Load Wallet',
                      icon: Icons.add_circle,
                      onPressed: () async {
                        final amount = double.tryParse(_amountController.text) ?? 0;
                        if (amount > 0) {
                          await context.read<WalletProvider>().load(amount);
                          if (mounted) context.read<NotificationsProvider>().add('Wallet Loaded', '₹${amount.toStringAsFixed(2)} added');
                        }
                      },
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class RechargeScreen extends StatefulWidget {
  static const route = '/recharge';
  const RechargeScreen({super.key});

  @override
  State<RechargeScreen> createState() => _RechargeScreenState();
}

class _RechargeScreenState extends State<RechargeScreen> {
  String _category = 'Prepaid';
  String? _operator;
  final _accountController = TextEditingController();
  final _amountController = TextEditingController(text: '199');
  List<String> _operators = [];

  @override
  void initState() {
    super.initState();
    _loadOperators();
  }

  Future<void> _loadOperators() async {
    final ops = await MockApi.getOperators(_category);
    setState(() {
      _operators = ops;
      _operator = ops.isNotEmpty ? ops.first : null;
    });
  }

  @override
  Widget build(BuildContext context) {
    final wallet = context.watch<WalletProvider>();
    return CaptureScaffold(
      title: 'Recharge Services',
      screenName: 'recharge',
      child: Padding(
        padding: EdgeInsets.all(16.w),
        child: Column(
          children: [
            Section(
              title: 'Select Service',
              icon: Icons.category,
              child: Column(
                children: [
                  DropdownButtonFormField<String>(
                    value: _category,
                    items: const [
                      DropdownMenuItem(value: 'Prepaid', child: Text('Prepaid')),
                      DropdownMenuItem(value: 'DTH', child: Text('DTH')),
                      DropdownMenuItem(value: 'FASTag', child: Text('FASTag')),
                    ],
                    onChanged: (v) {
                      if (v == null) return;
                      setState(() => _category = v);
                      _loadOperators();
                    },
                    decoration: const InputDecoration(labelText: 'Category'),
                  ),
                  SizedBox(height: 12.h),
                  DropdownButtonFormField<String>(
                    value: _operator,
                    items: _operators.map((e) => DropdownMenuItem(value: e, child: Text(e))).toList(),
                    onChanged: (v) => setState(() => _operator = v),
                    decoration: const InputDecoration(labelText: 'Operator'),
                  ),
                ],
              ),
            ),
            Section(
              title: 'Recharge Details',
              icon: Icons.receipt_long,
              child: Column(
                children: [
                  TextField(
                    controller: _accountController,
                    decoration: const InputDecoration(labelText: 'Mobile/DTH/FASTag number'),
                  ),
                  SizedBox(height: 12.h),
                  TextField(
                    controller: _amountController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(labelText: 'Amount'),
                  ),
                  SizedBox(height: 16.h),
                  PrimaryButton(
                    label: 'Proceed Recharge',
                    icon: Icons.flash_on,
                    onPressed: () async {
                      final operator = _operator ?? '';
                      final acc = _accountController.text;
                      final amount = double.tryParse(_amountController.text) ?? 0;
                      if (operator.isEmpty || acc.isEmpty || amount <= 0) return;
                      await context.read<WalletProvider>().addRecharge(operator, acc, amount);
                      if (mounted) {
                        context.read<NotificationsProvider>().add('Recharge Success', '$operator charged ₹${amount.toStringAsFixed(2)}');
                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Recharge processed')));
                      }
                    },
                  ),
                ],
              ),
            ),
            Text('Current Balance: ₹${wallet.balance.toStringAsFixed(2)}'),
          ],
        ),
      ),
    );
  }
}

class QrScanPayScreen extends StatefulWidget {
  static const route = '/qr-scan-pay';
  const QrScanPayScreen({super.key});

  @override
  State<QrScanPayScreen> createState() => _QrScanPayScreenState();
}

class _QrScanPayScreenState extends State<QrScanPayScreen> {
  final _descController = TextEditingController(text: 'Payment to Merchant');
  final _amountController = TextEditingController(text: '50');

  @override
  Widget build(BuildContext context) {
    final wallet = context.watch<WalletProvider>();
    return CaptureScaffold(
      title: 'QR Scan & Pay',
      screenName: 'qr_scan_pay',
      child: Padding(
        padding: EdgeInsets.all(16.w),
        child: Column(
          children: [
            Card(
              child: Padding(
                padding: EdgeInsets.all(16.w),
                child: Row(
                  children: [
                    const Icon(Icons.account_balance_wallet),
                    SizedBox(width: 8.w),
                    Text('Balance: ₹${wallet.balance.toStringAsFixed(2)}'),
                  ],
                ),
              ),
            ),
            SizedBox(height: 12.h),
            Section(
              title: 'Payment Details',
              icon: Icons.qr_code_scanner,
              child: Column(
                children: [
                  TextField(controller: _descController, decoration: const InputDecoration(labelText: 'Description')),
                  SizedBox(height: 12.h),
                  TextField(
                    controller: _amountController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(labelText: 'Amount'),
                  ),
                  SizedBox(height: 16.h),
                  PrimaryButton(
                    label: 'Pay',
                    icon: Icons.send,
                    onPressed: () async {
                      final amount = double.tryParse(_amountController.text) ?? 0;
                      final desc = _descController.text;
                      if (amount <= 0 || desc.isEmpty) return;
                      await context.read<WalletProvider>().pay(amount, desc);
                      if (mounted) context.read<NotificationsProvider>().add('Payment Success', 'Paid ₹${amount.toStringAsFixed(2)}');
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

import 'widgets/transaction_tile.dart';

class TransactionsScreen extends StatelessWidget {
  static const route = '/transactions';
  const TransactionsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final txs = context.watch<WalletProvider>().transactions;
    return CaptureScaffold(
      title: 'Transaction History',
      screenName: 'transactions',
      child: ListView.builder(
        itemCount: txs.length,
        itemBuilder: (_, i) {
          final t = txs[i];
          return TransactionTile(item: t);
        },
      ),
    );
  }
}

class AccountStatementScreen extends StatelessWidget {
  static const route = '/account-statement';
  const AccountStatementScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final txs = context.watch<WalletProvider>().transactions;
    final total = txs.fold<double>(0, (sum, t) => sum + t.amount);
    return CaptureScaffold(
      title: 'Account Statement',
      screenName: 'account_statement',
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(children: [const Icon(Icons.summarize), const SizedBox(width: 8), Text('Net total: ₹${total.toStringAsFixed(2)}')]),
            const SizedBox(height: 12),
            Expanded(
              child: ListView.builder(
                itemCount: txs.length,
                itemBuilder: (_, i) {
                  final t = txs[i];
                  return ListTile(
                    title: Text('${t.type} - ₹${t.amount.toStringAsFixed(2)}'),
                    subtitle: Text('${t.description}\n${t.date}'),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class NotificationsScreen extends StatelessWidget {
  static const route = '/notifications';
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final items = context.watch<NotificationsProvider>().items;
    return CaptureScaffold(
      title: 'Notifications',
      screenName: 'notifications',
      child: ListView.builder(
        itemCount: items.length,
        itemBuilder: (_, i) {
          final n = items[i];
          return ListTile(
            leading: const Icon(Icons.notifications),
            title: Text(n.title),
            subtitle: Text('${n.body}\n${n.date}'),
          );
        },
      ),
    );
  }
}

class ServiceRequestScreen extends StatefulWidget {
  static const route = '/service-request';
  const ServiceRequestScreen({super.key});

  @override
  State<ServiceRequestScreen> createState() => _ServiceRequestScreenState();
}

class _ServiceRequestScreenState extends State<ServiceRequestScreen> {
  final _subjectController = TextEditingController();
  final _messageController = TextEditingController();

  @override
  Widget build(BuildContext context) {
    return CaptureScaffold(
      title: 'Service Request',
      screenName: 'service_request',
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(controller: _subjectController, decoration: const InputDecoration(labelText: 'Subject')),
            const SizedBox(height: 12),
            TextField(controller: _messageController, maxLines: 4, decoration: const InputDecoration(labelText: 'Message')),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: () {
                if (_subjectController.text.isEmpty || _messageController.text.isEmpty) return;
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Request submitted')));
                Navigator.pop(context);
              },
              child: const Text('Submit'),
            ),
          ],
        ),
      ),
    );
  }
}