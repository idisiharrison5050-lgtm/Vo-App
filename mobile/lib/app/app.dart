import 'package:flutter/material.dart';

class VoApp extends StatelessWidget {
  const VoApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Vo-App',
      debugShowCheckedModeBanner: false,
      theme: VoTheme.light(),
      darkTheme: VoTheme.dark(),
      themeMode: ThemeMode.system,
      home: const AppShell(),
    );
  }
}

class VoTheme {
  static ThemeData light() {
    final scheme = ColorScheme.fromSeed(seedColor: const Color(0xFF111111), brightness: Brightness.light);
    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      scaffoldBackgroundColor: const Color(0xFFF7F7F5),
      appBarTheme: const AppBarTheme(backgroundColor: Colors.transparent, elevation: 0, scrolledUnderElevation: 0),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: Colors.white,
        indicatorColor: const Color(0xFFE9E9E7),
        labelTextStyle: WidgetStateProperty.all(const TextStyle(fontSize: 11, fontWeight: FontWeight.w600)),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
      ),
    );
  }

  static ThemeData dark() {
    final scheme = ColorScheme.fromSeed(seedColor: const Color(0xFFF4F4F0), brightness: Brightness.dark);
    return ThemeData(
      useMaterial3: true,
      colorScheme: scheme,
      scaffoldBackgroundColor: const Color(0xFF0B0B0B),
      appBarTheme: const AppBarTheme(backgroundColor: Colors.transparent, elevation: 0, scrolledUnderElevation: 0),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: const Color(0xFF111111),
        indicatorColor: const Color(0xFF292929),
        labelTextStyle: WidgetStateProperty.all(const TextStyle(fontSize: 11, fontWeight: FontWeight.w600)),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: const Color(0xFF171717),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
      ),
    );
  }
}

class AppShell extends StatefulWidget {
  const AppShell({super.key});

  @override
  State<AppShell> createState() => _AppShellState();
}

class _AppShellState extends State<AppShell> {
  int index = 0;

  final pages = const [HomePage(), NumbersPage(), MessagesPage(), WalletPage()];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(index: index, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: index,
        onDestinationSelected: (value) => setState(() => index = value),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.grid_view_rounded), selectedIcon: Icon(Icons.grid_view_rounded), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.sim_card_outlined), selectedIcon: Icon(Icons.sim_card_rounded), label: 'Numbers'),
          NavigationDestination(icon: Icon(Icons.chat_bubble_outline_rounded), selectedIcon: Icon(Icons.chat_bubble_rounded), label: 'Messages'),
          NavigationDestination(icon: Icon(Icons.account_balance_wallet_outlined), selectedIcon: Icon(Icons.account_balance_wallet_rounded), label: 'Wallet'),
        ],
      ),
    );
  }
}

class HomePage extends StatelessWidget {
  const HomePage({super.key});

  @override
  Widget build(BuildContext context) {
    final text = Theme.of(context).colorScheme.onSurface;
    final muted = Theme.of(context).colorScheme.onSurfaceVariant;

    return SafeArea(
      child: CustomScrollView(
        slivers: [
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 18, 20, 8),
            sliver: SliverToBoxAdapter(
              child: Row(
                children: [
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Good afternoon', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w500)),
                        SizedBox(height: 3),
                        Text('Harrison', style: TextStyle(fontSize: 30, fontWeight: FontWeight.w800, letterSpacing: -1)),
                      ],
                    ),
                  ),
                  Container(
                    width: 46,
                    height: 46,
                    decoration: BoxDecoration(
                      color: Theme.of(context).colorScheme.surface,
                      shape: BoxShape.circle,
                      boxShadow: const [BoxShadow(blurRadius: 18, offset: Offset(0, 5), spreadRadius: -10)],
                    ),
                    child: const Icon(Icons.notifications_none_rounded),
                  ),
                ],
              ),
            ),
          ),
          SliverPadding(padding: const EdgeInsets.fromLTRB(20, 14, 20, 0), sliver: SliverToBoxAdapter(child: _BalanceCard(text: text, muted: muted))),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 22, 20, 0),
            sliver: SliverToBoxAdapter(
              child: Row(children: [
                const Expanded(child: Text('Quick actions', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800))),
                Text('View all', style: TextStyle(color: muted, fontSize: 13, fontWeight: FontWeight.w600)),
              ]),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 0),
            sliver: SliverToBoxAdapter(
              child: Row(children: const [
                Expanded(child: _QuickAction(icon: Icons.add_rounded, label: 'Get a number')),
                SizedBox(width: 10),
                Expanded(child: _QuickAction(icon: Icons.south_west_rounded, label: 'Fund wallet')),
                SizedBox(width: 10),
                Expanded(child: _QuickAction(icon: Icons.autorenew_rounded, label: 'Renew')),
              ]),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 25, 20, 0),
            sliver: SliverToBoxAdapter(
              child: Row(children: const [
                Expanded(child: Text('Your numbers', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800))),
                Text('See all', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
              ]),
            ),
          ),
          SliverPadding(padding: const EdgeInsets.fromLTRB(20, 12, 20, 0), sliver: SliverToBoxAdapter(child: _NumberCard())),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 25, 20, 0),
            sliver: SliverToBoxAdapter(
              child: Row(children: const [
                Expanded(child: Text('Explore numbers', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800))),
                Text('Browse', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
              ]),
            ),
          ),
          SliverPadding(padding: const EdgeInsets.fromLTRB(20, 12, 20, 28), sliver: SliverToBoxAdapter(child: _ExploreCard())),
        ],
      ),
    );
  }
}

class _BalanceCard extends StatelessWidget {
  const _BalanceCard({required this.text, required this.muted});
  final Color text;
  final Color muted;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(28),
        color: Theme.of(context).brightness == Brightness.dark ? const Color(0xFF191919) : const Color(0xFF141414),
        boxShadow: const [BoxShadow(blurRadius: 28, offset: Offset(0, 14), spreadRadius: -18)],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(children: [Icon(Icons.account_balance_wallet_outlined, color: Colors.white70, size: 18), SizedBox(width: 8), Text('Available balance', style: TextStyle(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.w600))]),
          const SizedBox(height: 10),
          const Text('\$128.40', style: TextStyle(color: Colors.white, fontSize: 34, fontWeight: FontWeight.w800, letterSpacing: -1.2)),
          const SizedBox(height: 18),
          Row(children: [
            const Expanded(child: Text('USD wallet', style: TextStyle(color: Colors.white54, fontSize: 12))),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
              decoration: BoxDecoration(color: Colors.white.withValues(alpha: .09), borderRadius: BorderRadius.circular(30)),
              child: const Text('Fund wallet  →', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w700)),
            ),
          ]),
        ],
      ),
    );
  }
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({required this.icon, required this.label});
  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 92,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: Theme.of(context).colorScheme.surface, borderRadius: BorderRadius.circular(20)),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
        Container(width: 34, height: 34, decoration: BoxDecoration(color: Theme.of(context).colorScheme.surfaceContainerHighest, shape: BoxShape.circle), child: Icon(icon, size: 19)),
        Text(label, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700)),
      ]),
    );
  }
}

class _NumberCard extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(color: Theme.of(context).colorScheme.surface, borderRadius: BorderRadius.circular(24)),
      child: Column(children: [
        Row(children: [
          Container(width: 42, height: 42, decoration: BoxDecoration(color: Theme.of(context).colorScheme.surfaceContainerHighest, borderRadius: BorderRadius.circular(13)), child: const Icon(Icons.flag_outlined)),
          const SizedBox(width: 12),
          const Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('United States', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)), SizedBox(height: 3), Text('+1 415 555 0188', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, letterSpacing: -.3))])),
          Container(width: 9, height: 9, decoration: const BoxDecoration(color: Color(0xFF43A047), shape: BoxShape.circle)),
        ]),
        const SizedBox(height: 16),
        Row(children: const [Expanded(child: _Meta(label: 'Plan', value: 'Monthly')), Expanded(child: _Meta(label: 'Renews', value: 'Oct 11'))]),
      ]),
    );
  }
}

class _Meta extends StatelessWidget {
  const _Meta({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Text(label, style: TextStyle(fontSize: 11, color: Theme.of(context).colorScheme.onSurfaceVariant)),
      const SizedBox(height: 3),
      Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700)),
    ],
  );
}

class _ExploreCard extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(borderRadius: BorderRadius.circular(25), border: Border.all(color: Theme.of(context).dividerColor)),
      child: Row(children: [
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: const [Text('Find your next number', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)), SizedBox(height: 6), Text('Choose a country, capability and term that fits you.', style: TextStyle(fontSize: 12, height: 1.4))])),
        const SizedBox(width: 14),
        Container(width: 48, height: 48, decoration: BoxDecoration(color: Theme.of(context).colorScheme.onSurface, shape: BoxShape.circle), child: Icon(Icons.arrow_forward_rounded, color: Theme.of(context).colorScheme.surface)),
      ]),
    );
  }
}

class NumbersPage extends StatelessWidget {
  const NumbersPage({super.key});
  @override
  Widget build(BuildContext context) => const _PlaceholderPage(title: 'Numbers', subtitle: 'Your active numbers and the global marketplace will live here.');
}

class MessagesPage extends StatelessWidget {
  const MessagesPage({super.key});
  @override
  Widget build(BuildContext context) => const _PlaceholderPage(title: 'Messages', subtitle: 'Incoming SMS will appear here in real time.');
}

class WalletPage extends StatelessWidget {
  const WalletPage({super.key});
  @override
  Widget build(BuildContext context) => const _PlaceholderPage(title: 'Wallet', subtitle: 'Fund your wallet and review your transaction history.');
}

class _PlaceholderPage extends StatelessWidget {
  const _PlaceholderPage({required this.title, required this.subtitle});
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) => SafeArea(
    child: Padding(
      padding: const EdgeInsets.all(22),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 22),
          Text(title, style: const TextStyle(fontSize: 30, fontWeight: FontWeight.w800, letterSpacing: -1)),
          const SizedBox(height: 10),
          Text(subtitle, style: TextStyle(color: Theme.of(context).colorScheme.onSurfaceVariant, height: 1.5)),
        ],
      ),
    ),
  );
}
