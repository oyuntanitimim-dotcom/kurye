import 'package:flutter/material.dart';

class GlassCard extends StatelessWidget {
  const GlassCard({
    super.key,
    required this.child,
    this.padding,
    this.borderRadius = 18,
  });

  final Widget child;
  final EdgeInsetsGeometry? padding;
  final double borderRadius;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    final Widget inner = padding == null ? child : Padding(padding: padding!, child: child);

    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(borderRadius),
        color: isDark ? Colors.white.withValues(alpha: 0.06) : theme.cardColor,
        border: isDark ? Border.all(color: Colors.white.withValues(alpha: 0.08)) : null,
      ),
      child: inner,
    );
  }
}

