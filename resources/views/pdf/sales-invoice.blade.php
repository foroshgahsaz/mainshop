<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $document->title }} {{ $document->invoiceNumber }}</title>
</head>
<body>
    @include('components.sales-invoice.document', ['document' => $document, 'context' => 'pdf'])
</body>
</html>
