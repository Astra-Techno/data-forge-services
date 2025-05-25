<!-- resources/views/upload.blade.php -->
<form action="/import" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="csv_file" accept=".csv">
    <button type="submit">Import</button>
</form>