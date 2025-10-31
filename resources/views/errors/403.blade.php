<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>403 無權限</title>

    <!-- ✅ Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 text-gray-800">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="bg-white p-8 rounded-xl shadow-md text-center max-w-md w-full space-y-4">
            <!-- Icon -->
            <div class="text-yellow-500 flex justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.054 0 1.618-1.14 1.05-2L13.05 4c-.53-.882-1.87-.882-2.4 0L4.032 17c-.568.86-.004 2 1.05 2z" />
                </svg>
            </div>

            <h1 class="text-xl font-bold">403 無權限</h1>
            <p class="text-gray-500">您沒有權限瀏覽此頁面，請洽管理員或重新登入。</p>

            <div class="flex justify-center gap-2 pt-2">
                <form method="POST" action="{{ route('logout') }}">
                    <!-- Laravel logout CSRF -->
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition">
                        登出
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
