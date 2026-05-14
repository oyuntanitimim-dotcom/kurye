import 'dart:convert';

/// Minimal raster style for MapLibre.
///
/// Converts a raster tile template into a MapLibre Style JSON string.
String buildRasterStyleJson({
  required String tilesUrlTemplate,
  String attribution = 'OpenStreetMap contributors',
}) {
  final style = {
    'version': 8,
    'sources': {
      'raster-tiles': {
        'type': 'raster',
        'tiles': [tilesUrlTemplate.replaceAll('{s}', 'a')],
        'tileSize': 256,
        'attribution': attribution,
      },
    },
    'layers': [
      {
        'id': 'raster-tiles',
        'type': 'raster',
        'source': 'raster-tiles',
      },
    ],
  };

  return jsonEncode(style);
}

