@php
    $headerSnippets = app(\App\Services\Settings\SiteHeaderSettingsService::class)->activeSnippets();
@endphp
@foreach ($headerSnippets as $snippet)
    @if ($snippet['defer'])
        <script>
            (function () {
                var html = @json($snippet['code']);
                function injectSiteHeaderSnippet() {
                    var tpl = document.createElement('template');
                    tpl.innerHTML = html.trim();
                    tpl.content.querySelectorAll('script').forEach(function (oldScript) {
                        var script = document.createElement('script');
                        Array.prototype.forEach.call(oldScript.attributes, function (attr) {
                            script.setAttribute(attr.name, attr.value);
                        });
                        script.text = oldScript.text;
                        (document.head || document.documentElement).appendChild(script);
                    });
                    Array.prototype.forEach.call(tpl.content.childNodes, function (node) {
                        if (node.nodeName && node.nodeName.toLowerCase() !== 'script') {
                            (document.head || document.documentElement).appendChild(node.cloneNode(true));
                        }
                    });
                }
                if ('requestIdleCallback' in window) {
                    requestIdleCallback(injectSiteHeaderSnippet, { timeout: 4000 });
                } else {
                    window.addEventListener('load', function () {
                        setTimeout(injectSiteHeaderSnippet, 2000);
                    });
                }
            })();
        </script>
    @else
        {!! $snippet['code'] !!}
    @endif
@endforeach
