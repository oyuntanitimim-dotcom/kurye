import 'package:flutter/foundation.dart';

class ShellNavStore extends ChangeNotifier {
  int _tab = 0;

  int get tab => _tab;

  void setTab(int next) {
    if (next == _tab) return;
    _tab = next;
    notifyListeners();
  }
}

