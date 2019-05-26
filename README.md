Pinboard 0.8.4
==========
Pinboard for timed notices.

<p align="center"><img src="pinboard-screenshot.png?raw=true" alt="Screenshot"></p>

## How to install extension

1. [Download and install Datenstrom Yellow](https://github.com/datenstrom/yellow/).
2. [Download extension](../../archive/master.zip). If you are using Safari, right click and select 'Download file as'.
3. Copy `pinboard.zip` into your `system/extensions` folder.

To uninstall delete the [extension files](extension.ini).

## How to create a pinboard

Put one or more notices files into `media/pinboard/`. You can use different formats (choose whichever you like better).

Notices in a `.yaml` file (each notice begins with `---`):

    ---
    start: YYYY-MM-DD
    end: YYYY-MM-DD
    class: text
    content: text
    tags: tag tag...

Notices in a `.psv` file (one notice per line):

    YYYY-MM-DD | YYYY-MM-DD | class | content | tags

Notices can be written also in a `.tsv` or a `.csv` format (in this latter, content must be wrapped in quotes if it contains commas).

The `start` and `end` dates specify the time interval in which the notice is shown (end date is meant inclusive). The `class`, if present, is used to style the notice; notices classed as `pinned` are moreover listed at the top. The standard styles define the classes `important`, `urgent` and `pinned`: other classes can be freely added as needed.

In `content`, use `*` for italic, `**` for bold, `[text](URL)` for linking, `\n` for newline. Other URLs and email addresses are autolinked.

## How to embed a pinboard

Create a `[pinboard]` shortcut.

The following arguments are available, all but the first argument are optional:

`Location` = filename of notices list to show  
`TimeSpan` (default: `current`) = show `current` or `past` notices  
`Max` (default: `0`) = maximum number of notices to show, 0 for unlimited  
`Tags` = show only notices with any of the tags, wrap multiple tags into quotes  

## Settings

The following settings can be configured in file `system/settings/system.ini`.

`PinboardDir` (default: `media/pinboard/`) = directory for Pinboard files  
`PinboardStyle` (default: `plain`) = pinboard style (you can choose between `plain` and `icons`) 

If you want to add a new `fancy` style, write a `pinboard-fancy.css`  file and put into the `system/extensions` folder. Do not modify the standard styles, since they will be overwritten in case of update of the extension.

## Examples

Showing the pinboard of all current notices:

    [pinboard notices.psv]
    [pinboard notices.yaml]

Showing the pinboard with various options:

    [pinboard notices.psv past]
    [pinboard notices.psv current 5]
    [pinboard notices.yaml current 0 freetime]

## Developer

Giovanni Salmeri.
