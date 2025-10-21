import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  // TODO: Initialize Firebase here when configs are available.
  runApp(const MegaPayApp());
}

class MegaPayApp extends StatelessWidget {
  const MegaPayApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) =&gt; AuthProvider()),
      ],
      child: MaterialApp(
        title: 'MegaPay',
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF2D9CDB)),
          useMaterial3: true,
          textTheme: GoogleFonts.interTextTheme(),
        ),
        home: const AppHome(),
        routes: {
          SignInScreen.route: (_) =&gt; const SignInScreen(),
          SignUpScreen.route: (_) =&gt; const SignUpScreen(),
          ResetPasswordScreen.route: (_) =&gt; const ResetPasswordScreen(),
          WalletScreen.route: (_) =&gt; const WalletScreen(),
          RechargeScreen.route: (_) =&gt; const RechargeScreen(),
          QrScanPayScreen.route: (_) =&gt; const QrScanPayScreen(),
          TransactionsScreen.route: (_) =&gt; const TransactionsScreen(),
          AccountStatementScreen.route: (_) =&gt; const AccountStatementScreen(),
          NotificationsScreen.route: (_) =&gt; const NotificationsScreen(),
          ServiceRequestScreen.route: (_) =&gt; const ServiceRequestScreen(),
        },
      ),
    );
  }
}

class AuthProvider extends ChangeNotifier {
  bool _signedIn = false;
  String? _email;

  bool get signedIn =&gt; _signedIn;
  String? get email =&gt; _email;

  Future&lt;void&gt; signIn(String email, String password) async {
    // TODO: Integrate real auth via Firebase or your backend.
    await Future.delayed(const Duration(milliseconds: 400));
    _email = email;
    _signedIn = true;
    notifyListeners();
  }

  Future&lt;void&gt; signUp(String email, String password) async {
    // TODO: Integrate sign up
    await Future.delayed(const Duration(milliseconds: 400));
    _email = email;
    _signedIn = true;
    notifyListeners();
  }

  Future&lt;void&gt; resetPassword(String email) async {
    // TODO: Integrate password reset
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
    final signedIn = context.watch&lt;AuthProvider&gt;().signedIn;

    return Scaffold(
      appBar: AppBar(
        title: const Text('MegaPay'),
        actions: [
          IconButton(
            icon: Icon(signedIn ? Icons.logout : Icons.login),
            onPressed: () {
              if (signedIn) {
                context.read&lt;AuthProvider&gt;().signOut();
              } else {
                Navigator.pushNamed(context, SignInScreen.route);
              }
            },
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: const [
          FeatureTile('User Registration', SignUpScreen.route, Icons.person_add),
          FeatureTile('User Sign In', SignInScreen.route, Icons.login),
          FeatureTile('Reset Password', ResetPasswordScreen.route, Icons.lock_reset),
          FeatureTile('Wallet Cashload', WalletScreen.route, Icons.account_balance_wallet),
          FeatureTile('Recharge Services', RechargeScreen.route, Icons.bolt),
          FeatureTile('QR Scan &amp; Pay', QrScanPayScreen.route, Icons.qr_code_scanner),
          FeatureTile('Transaction History', TransactionsScreen.route, Icons.receipt_long),
          FeatureTile('Account Statement', AccountStatementScreen.route, Icons.description_outlined),
          FeatureTile('Push Notification', NotificationsScreen.route, Icons.notifications),
          FeatureTile('Service Request', ServiceRequestScreen.route, Icons.support_agent),
        ],
      ),
    );
  }
}

class FeatureTile extends StatelessWidget {
  final String title;
  final String route;
  final IconData icon;
  const FeatureTile(this.title, this.route, this.icon, {super.key});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        leading: Icon(icon),
        title: Text(title),
        trailing: const Icon(Icons.chevron_right),
        onTap: () =&gt; Navigator.pushNamed(context, route),
      ),
    );
  }
}

// Screens - placeholders to be expanded

class SignInScreen extends StatefulWidget {
  static const route = '/signin';
  const SignInScreen({super.key});

  @override
  State&lt;SignInScreen&gt; createState() =&gt; _SignInScreenState();
}

class _SignInScreenState extends State&lt;SignInScreen&gt; {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _loading = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sign In')),
      body: Padding(
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
                      setState(() =&gt; _loading = true);
                      await context.read&lt;AuthProvider&gt;().signIn(_emailController.text, _passwordController.text);
                      if (mounted) Navigator.pop(context);
                      setState(() =&gt; _loading = false);
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
  State&lt;SignUpScreen&gt; createState() =&gt; _SignUpScreenState();
}

class _SignUpScreenState extends State&lt;SignUpScreen&gt; {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _loading = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sign Up')),
      body: Padding(
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
                      setState(() =&gt; _loading = true);
                      await context.read&lt;AuthProvider&gt;().signUp(_emailController.text, _passwordController.text);
                      if (mounted) Navigator.pop(context);
                      setState(() =&gt; _loading = false);
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
  State&lt;ResetPasswordScreen&gt; createState() =&gt; _ResetPasswordScreenState();
}

class _ResetPasswordScreenState extends State&lt;ResetPasswordScreen&gt; {
  final _emailController = TextEditingController();
  bool _loading = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Reset Password')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(controller: _emailController, decoration: const InputDecoration(labelText: 'Email')),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _loading
                  ? null
                  : () async {
                      setState(() =&gt; _loading = true);
                      await context.read&lt;AuthProvider&gt;().resetPassword(_emailController.text);
                      if (mounted) Navigator.pop(context);
                      setState(() =&gt; _loading = false);
                    },
              child: _loading ? const CircularProgressIndicator() : const Text('Send Reset Link'),
            ),
          ],
        ),
      ),
    );
  }
}

class WalletScreen extends StatelessWidget {
  static const route = '/wallet';
  const WalletScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Wallet')),
      body: const Center(child: Text('Wallet cashload and balance view - to implement')),
    );
  }
}

class RechargeScreen extends StatelessWidget {
  static const route = '/recharge';
  const RechargeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Recharge Services')),
      body: const Center(child: Text('Prepaid/DTH/FASTag recharge flows - to implement')),
    );
  }
}

class QrScanPayScreen extends StatelessWidget {
  static const route = '/qr-scan-pay';
  const QrScanPayScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('QR Scan &amp; Pay')),
      body: const Center(child: Text('QR scanner and payment - to implement')),
    );
  }
}

class TransactionsScreen extends StatelessWidget {
  static const route = '/transactions';
  const TransactionsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Transaction History')),
      body: const Center(child: Text('List past transactions - to implement')),
    );
  }
}

class AccountStatementScreen extends StatelessWidget {
  static const route = '/account-statement';
  const AccountStatementScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Account Statement')),
      body: const Center(child: Text('Exportable statement view - to implement')),
    );
  }
}

class NotificationsScreen extends StatelessWidget {
  static const route = '/notifications';
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Notifications')),
      body: const Center(child: Text('Push notification preferences - to implement')),
    );
  }
}

class ServiceRequestScreen extends StatelessWidget {
  static const route = '/service-request';
  const ServiceRequestScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Service Request')),
      body: const Center(child: Text('Raise and track service requests - to implement')),
    );
  }
}