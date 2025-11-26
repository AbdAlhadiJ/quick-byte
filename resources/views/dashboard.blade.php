<!-- resources/views/admin/oauth-dashboard.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Authorization Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900">
<div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-black shadow p-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl text-white font-bold align-content-around">
                <img src="{{asset('images/logo-100.png')}}" alt="QuickByte Logo" width="50px" class="inline-block mr-2">
                Dashboard</h1>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition">
                Logout
            </button>
        </form>
    </header>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto p-6 space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- TikTok Card -->
            <div class="bg-white rounded-2xl shadow p-6 flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-semibold mb-2">TikTok</h2>
                    <p class="text-gray-600 mb-4">Authorize to enable video uploads & analytics.</p>
                </div>
                <a href="javascript:void(0);" @click.prevent="authorize('tiktok')"
                   class="mt-auto inline-block bg-gradient-to-r from-pink-500 to-red-500 text-white font-medium py-2 px-4 rounded-xl shadow hover:opacity-90 transition-opacity">
                    Authorize TikTok
                </a>
            </div>

            <!-- YouTube Card -->
            <div class="bg-white rounded-2xl shadow p-6 flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-semibold mb-2">YouTube</h2>
                    <p class="text-gray-600 mb-4">Authorize to post videos directly to your channel.</p>
                </div>
                <a href="javascript:void(0);" @click.prevent="authorize('youtube')"
                   class="mt-auto inline-block bg-gradient-to-r from-red-600 to-yellow-500 text-white font-medium py-2 px-4 rounded-xl shadow hover:opacity-90 transition-opacity">
                    Authorize YouTube
                </a>
            </div>

            <!-- Instagram Card -->
            <div class="bg-white rounded-2xl shadow p-6 flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-semibold mb-2">Instagram</h2>
                    <p class="text-gray-600 mb-4">Authorize to schedule and publish Reels.</p>
                </div>
                <a href="javascript:void(0);" @click.prevent="authorize('instagram')"
                   class="mt-auto inline-block bg-gradient-to-r from-purple-500 to-pink-400 text-white font-medium py-2 px-4 rounded-xl shadow hover:opacity-90 transition-opacity">
                    Authorize Instagram
                </a>
            </div>
        </div>

        <!-- Settings Section -->
        <section class="bg-white rounded-2xl shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Website Links Settings</h2>
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="tiktok_url" class="block text-sm font-medium text-gray-700">TikTok Profile URL</label>
                        <input type="url" name="tiktok_url" id="tiktok_url" value="{{ old('tiktok_url', config('services.links.tiktok')) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"/>
                    </div>
                    <div>
                        <label for="youtube_url" class="block text-sm font-medium text-gray-700">YouTube Channel URL</label>
                        <input type="url" name="youtube_url" id="youtube_url" value="{{ old('youtube_url', config('services.links.youtube')) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"/>
                    </div>
                    <div>
                        <label for="instagram_url" class="block text-sm font-medium text-gray-700">Instagram Profile URL</label>
                        <input type="url" name="instagram_url" id="instagram_url" value="{{ old('instagram_url', config('services.links.instagram')) }}"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"/>
                    </div>
                </div>
                <button type="submit"
                        class="mt-4 bg-indigo-600 text-white font-medium py-2 px-6 rounded-xl hover:bg-indigo-700 transition">
                    Save Settings
                </button>
            </form>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-white shadow p-4 text-center text-sm text-gray-500">
        © {{ date('Y') }} QuickByte Dashboard
    </footer>
</div>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>

<script>
    const { createApp } = Vue;

    createApp({
        data() {
            return {
                endpoints: {
                    tiktok: "{{ route('admin.authorize',['platform' => 'tiktok']) }}",
                    youtube: "{{ route('admin.authorize',['platform' => 'youtube']) }}",
                    instagram: "{{ route('admin.authorize',['platform' => 'instagram']) }}"
                },
            }
        },
        methods: {
            authorize(platform) {
                const url = this.endpoints[platform];
                if (!url) {
                    console.error('Unknown platform:', platform);
                    return;
                }

                axios.get(url)
                    .then(res => {
                        const link = res.data.authorize_url;
                        if (link) {
                            window.open(link, '_blank');
                        } else {
                            console.error('No authorize_url in response', res.data);
                        }
                    })
                    .catch(err => {
                        console.error('Authorization request failed:', err);
                        alert('Could not start authorization. Check console for details.');
                    });
            }
        }
    }).mount('body');

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    @if(session('status'))
        toastr.options = {
        "positionClass": "toast-top-right",
        "timeOut": "5000",
        "closeButton": true
    };
    toastr.success("{{ session('status') }}");
    @endif
</script>
</script>

</body>
</html>
