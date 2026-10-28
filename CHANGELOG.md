# Changelog

## 2.0.0 (unreleased)

### Breaking changes

- The file action menu's Select command now starts multi-select mode instead of emitting `teknikhub-file-manager:file-selected`. Integrations that relied on this command must adapt their selection flow.
- Folders now open on a single click. Use Select or Ctrl/Cmd-click in the standalone manager to select folders for bulk actions.
- Popup selection sends the file URL to the opener's `fmSetLink` callback, or dispatches `teknikhub-file-manager:file-selected` on the opener document, then closes the popup. Popup integrations should listen on the opener document.

### Added

- Popup picker confirmation footer with the selected filename and thumbnail or icon.
- Multi-select mode through the standalone file action menu.
- Unicode upload filenames, including Arabic letters and diacritics, with a regression test.

### Fixed

- Interpolate the actual file path in the Select menu action.

### Changed

- Refine picker footer and file action styling.
- Remove the duplicate file manager stylesheet.

## 1.0.0

- Initial release.
