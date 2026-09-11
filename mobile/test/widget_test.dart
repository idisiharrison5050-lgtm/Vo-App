import 'package:flutter_test/flutter_test.dart';

import 'package:vo_app/main.dart';

void main() {
  testWidgets('Vo-App renders the premium home shell', (WidgetTester tester) async {
    await tester.pumpWidget(const VoApp());

    expect(find.text('Good afternoon'), findsOneWidget);
    expect(find.text('Harrison'), findsOneWidget);
    expect(find.text('Available balance'), findsOneWidget);
    expect(find.text('Your numbers'), findsOneWidget);
    expect(find.text('Explore numbers'), findsOneWidget);
    expect(find.text('Home'), findsOneWidget);
    expect(find.text('Numbers'), findsOneWidget);
    expect(find.text('Messages'), findsOneWidget);
    expect(find.text('Wallet'), findsOneWidget);
  });
}
