@php($ff = function($file){ $p = public_path('fonts/'.$file); return is_file($p) ? $p : storage_path('fonts/'.$file); })
<style>
    @font-face { font-family: 'Geomanist'; font-weight: 200; font-style: normal; src: url("{{ $ff('Geomanist-ExtraLight.ttf') }}") format("truetype"); }
    @font-face { font-family: 'Geomanist'; font-weight: 300; font-style: normal; src: url("{{ $ff('Geomanist-Light.ttf') }}") format("truetype"); }
    @font-face { font-family: 'Geomanist'; font-weight: 350; font-style: normal; src: url("{{ $ff('Geomanist-Book.ttf') }}") format("truetype"); }
    @font-face { font-family: 'Geomanist'; font-weight: 400; font-style: normal; src: url("{{ $ff('Geomanist-Regular.ttf') }}") format("truetype"); }
    @font-face { font-family: 'Geomanist'; font-weight: 500; font-style: normal; src: url("{{ $ff('Geomanist-Medium.ttf') }}") format("truetype"); }
    @font-face { font-family: 'Geomanist'; font-weight: 700; font-style: normal; src: url("{{ $ff('Geomanist-Bold.ttf') }}") format("truetype"); }

    @page { margin: 0; }
    * { font-family: 'Geomanist', 'DejaVu Sans', sans-serif; }
    body { font-size: 12px; font-weight: 300; color: #101015; margin: 0; background: #ffffff; }

    .wrap { padding: 40px 44px; }
    .head { border-bottom: 2px solid #EFEBDD; padding-bottom: 16px; margin-bottom: 22px; }
    .logo-pdf { max-height: 38px; max-width: 200px; }
    .marca { font-family: 'Geomanist'; font-weight: 350; font-size: 17px; letter-spacing: 4px; text-transform: uppercase; color: #101015; }
    .doc-title { font-family: 'Geomanist'; font-weight: 200; font-size: 19px; letter-spacing: .5px; margin: 2px 0 0; color: #101015; }
    .muted { color: #8a8578; }

    table { width: 100%; border-collapse: collapse; }
    .tbl { margin-top: 4px; }
    .tbl th { background: #EFEBDD; text-align: left; padding: 9px 10px; font-size: 10px; font-weight: 500; letter-spacing: .5px; text-transform: uppercase; color: #101015; border-bottom: 1px solid #e3ddcb; }
    .tbl td { padding: 9px 10px; border-bottom: 1px solid #f0eee9; font-size: 11px; font-weight: 300; vertical-align: top; }
    .right { text-align: right; }

    .box { border: 1px solid #EFEBDD; border-radius: 10px; padding: 13px 15px; margin-bottom: 13px; background: #ffffff; }
    .row2 { width: 100%; }
    .row2 td { width: 50%; vertical-align: top; }
    h4 { font-family: 'Geomanist'; font-weight: 500; margin: 0 0 6px; font-size: 10px; letter-spacing: .6px; text-transform: uppercase; color: #8a8578; }

    .total { font-family: 'Geomanist'; font-weight: 350; font-size: 15px; color: #101015; }
    .firma { border-top: 1px solid #EFEBDD; margin-top: 46px; padding-top: 5px; font-size: 10px; font-weight: 300; text-align: center; color: #8a8578; }
    .foot { margin-top: 28px; color: #b8b4ac; font-size: 9px; letter-spacing: .4px; text-align: center; }
    strong, b { font-weight: 500; }
</style>
