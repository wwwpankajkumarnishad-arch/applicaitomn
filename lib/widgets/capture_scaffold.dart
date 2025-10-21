import 'package:flutter/material.dart';
import '../utils/screenshot.dart';

class CaptureScaffold extends StatefulWidget {
  final String title;
  final String screenName;
  final Widget child;

  const CaptureScaffold({
    super.key,
    required this.title,
    required this.screenName,
    required this.child,
  });

  @override
  State&lt;CaptureScaffold&gt; createState() =&gt; _CaptureScaffoldState();
}

class _CaptureScaffoldState extends State&lt;CaptureScaffold&gt; {
  final _boundaryKey = GlobalKey();
  bool _saving = false;
  String? _lastPath;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      body: RepaintBoundary(
        key: _boundaryKey,
        child: widget.child,
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _saving
            ? null
            : () async {
                setState(() =&gt; _saving = true);
                final ts = DateTime.now().toIso8601String().replaceAll(':', '-');
                final file = await ScreenshotCapturer.saveBoundary(_boundaryKey, '${widget.screenName}_$ts');
                setState(() {
                  _saving = false;
                  _lastPath = file?.path;
                });
                if (!mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(
                      file == null ? 'Failed to save screenshot' : 'Saved: ${file.path}',
                    ),
                  ),
                );
              },
        icon: _saving ? const CircularProgressIndicator() : const Icon(Icons.camera_alt),
        label: const Text('Capture'),
      ),
      bottomNavigationBar: _lastPath == null
          ? null
          : Padding(
              padding: const EdgeInsets.all(8.0),
              child: Text(
                'Last screenshot: ${_lastPath!}',
                textAlign: TextAlign.center,
              ),
            ),
    );
  }
}