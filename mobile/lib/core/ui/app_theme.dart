import 'package:flutter/material.dart';

class AppThemeTokens {
  const AppThemeTokens._({
    required this.seed,
    required this.scaffoldBg,
    required this.surface,
    required this.surface2,
    required this.textPrimary,
    required this.textSecondary,
    required this.outline,
    required this.navHeight,
    required this.radiusCard,
    required this.radiusControl,
  });

  final Color seed;
  final Color scaffoldBg;
  final Color surface;
  final Color surface2;
  final Color textPrimary;
  final Color textSecondary;
  final Color outline;

  final double navHeight;
  final double radiusCard;
  final double radiusControl;

  static const light = AppThemeTokens._(
    seed: Color(0xff16a34a), // lively green accent like reference
    scaffoldBg: Color(0xfff6f7fb),
    surface: Colors.white,
    surface2: Color(0xfff8fafc),
    textPrimary: Color(0xff0f172a),
    textSecondary: Color(0xff475569),
    outline: Color(0xffe5e7eb),
    navHeight: 64,
    radiusCard: 16,
    radiusControl: 14,
  );

  static const dark = AppThemeTokens._(
    seed: Color(0xff22c55e), // green pops on dark
    scaffoldBg: Color(0xff0b1020),
    surface: Color(0xff0f172a),
    surface2: Color(0xff111c34),
    textPrimary: Color(0xffe5e7eb),
    textSecondary: Color(0xff94a3b8),
    outline: Color(0xff1f2a44),
    navHeight: 64,
    radiusCard: 16,
    radiusControl: 14,
  );
}

ThemeData buildLightAppTheme() => _buildTheme(tokens: AppThemeTokens.light, brightness: Brightness.light);

ThemeData buildDarkAppTheme() => _buildTheme(tokens: AppThemeTokens.dark, brightness: Brightness.dark);

ThemeData _buildTheme({required AppThemeTokens tokens, required Brightness brightness}) {
  final scheme = ColorScheme.fromSeed(
    seedColor: tokens.seed,
    brightness: brightness,
    surface: tokens.surface,
  );

  final base = ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    brightness: brightness,
  );

  final titleColor = tokens.textPrimary;
  final secondary = tokens.textSecondary;

  return base.copyWith(
    scaffoldBackgroundColor: tokens.scaffoldBg,
    appBarTheme: base.appBarTheme.copyWith(
      centerTitle: false,
      backgroundColor: Colors.transparent,
      elevation: 0,
      scrolledUnderElevation: 0,
      titleTextStyle: base.textTheme.titleLarge?.copyWith(
        fontWeight: FontWeight.w800,
        color: titleColor,
      ),
    ),
    cardTheme: base.cardTheme.copyWith(
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(tokens.radiusCard)),
      color: tokens.surface,
    ),
    dividerTheme: base.dividerTheme.copyWith(color: tokens.outline),
    inputDecorationTheme: base.inputDecorationTheme.copyWith(
      filled: true,
      fillColor: tokens.surface,
      labelStyle: TextStyle(color: tokens.textSecondary),
      floatingLabelStyle: TextStyle(color: scheme.primary),
      hintStyle: TextStyle(color: tokens.textSecondary.withValues(alpha: 0.85)),
      helperStyle: TextStyle(color: tokens.textSecondary),
      errorStyle: TextStyle(color: scheme.error),
      prefixIconColor: tokens.textSecondary,
      suffixIconColor: tokens.textSecondary,
      iconColor: tokens.textSecondary,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(tokens.radiusControl),
        borderSide: BorderSide(color: tokens.outline),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(tokens.radiusControl),
        borderSide: BorderSide(color: tokens.outline),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(tokens.radiusControl),
        borderSide: BorderSide(color: scheme.primary, width: 1.5),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(tokens.radiusControl),
        borderSide: BorderSide(color: scheme.error, width: 1.2),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(tokens.radiusControl),
        borderSide: BorderSide(color: scheme.error, width: 1.6),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(tokens.radiusControl)),
        textStyle: const TextStyle(fontWeight: FontWeight.w700),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(tokens.radiusControl)),
        side: BorderSide(color: tokens.outline),
        textStyle: const TextStyle(fontWeight: FontWeight.w700),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        textStyle: const TextStyle(fontWeight: FontWeight.w700),
      ),
    ),
    navigationBarTheme: base.navigationBarTheme.copyWith(
      height: tokens.navHeight,
      backgroundColor: tokens.surface,
      indicatorColor: brightness == Brightness.dark
          ? scheme.primary.withValues(alpha: 0.18)
          : scheme.primaryContainer,
      labelTextStyle: WidgetStatePropertyAll(
        base.textTheme.labelMedium?.copyWith(fontWeight: FontWeight.w700),
      ),
    ),
    listTileTheme: base.listTileTheme.copyWith(iconColor: scheme.primary),
    textTheme: base.textTheme.copyWith(
      titleLarge: base.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800, color: titleColor),
      titleMedium: base.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800, color: titleColor),
      titleSmall: base.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800, color: titleColor),
      bodyLarge: base.textTheme.bodyLarge?.copyWith(color: titleColor),
      bodyMedium: base.textTheme.bodyMedium?.copyWith(color: titleColor),
      bodySmall: base.textTheme.bodySmall?.copyWith(color: secondary),
      labelLarge: base.textTheme.labelLarge?.copyWith(color: secondary),
      labelSmall: base.textTheme.labelSmall?.copyWith(color: secondary),
    ),
    textSelectionTheme: TextSelectionThemeData(
      cursorColor: scheme.primary,
      selectionColor: scheme.primary.withValues(alpha: 0.28),
      selectionHandleColor: scheme.primary,
    ),
  );
}

