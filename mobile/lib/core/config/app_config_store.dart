import 'package:flutter/foundation.dart';
import 'package:kurye_mobile/core/config/app_config.dart';

class AppConfigStore extends ChangeNotifier {
  AppConfigStore(this._config);

  AppConfig _config;

  AppConfig get config => _config;

  void update(AppConfig next) {
    _config = next;
    notifyListeners();
  }
}

