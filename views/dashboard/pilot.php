<?php
/** @var array $data */
$pageTitle = 'Pilot Dashboard';
?>

<div class="space-y-6">
    <!-- Welcome Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Welcome, <?= e($_SESSION['user']['name']) ?></h1>
            <p class="text-slate-400">Mobile Operational Shell & Crew Sync</p>
        </div>
        <div class="px-4 py-2 bg-slate-800/50 rounded-xl border border-slate-700/50 backdrop-blur-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-widest block">Last Sync</span>
            <span class="text-sm font-medium text-emerald-400"><?= $data['sync_status'] ? date('d M H:i', strtotime($data['sync_status'])) : 'Never' ?></span>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-slate-900/50 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-blue-500/10 rounded-2xl text-blue-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-semibold text-white">Duty Status</h3>
            </div>
            <div class="text-2xl font-bold text-white mb-1">On Duty</div>
            <p class="text-sm text-slate-500">Standby - DXB Base</p>
        </div>

        <div class="bg-slate-900/50 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-purple-500/10 rounded-2xl text-purple-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="font-semibold text-white">Next Flight</h3>
            </div>
            <div class="text-2xl font-bold text-white mb-1">EK 201</div>
            <p class="text-sm text-slate-500">Scheduled: Tomorrow 08:30</p>
        </div>

        <div class="bg-slate-900/50 border border-slate-800 rounded-3xl p-6 backdrop-blur-xl">
            <div class="flex items-center gap-4 mb-4">
                <div class="p-3 bg-emerald-500/10 rounded-2xl text-emerald-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="font-semibold text-white">Documents</h3>
            </div>
            <div class="text-2xl font-bold text-white mb-1">Up to Date</div>
            <p class="text-sm text-slate-500">14 Manuals Synchronized</p>
        </div>
    </div>

    <!-- Recent Notices -->
    <div class="bg-slate-900/50 border border-slate-800 rounded-3xl overflow-hidden backdrop-blur-xl">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center">
            <h3 class="font-bold text-white uppercase text-sm tracking-wider">Company Notices</h3>
            <a href="/notices" class="text-blue-400 text-xs font-semibold hover:text-blue-300 transition-colors">VIEW ALL</a>
        </div>
        <div class="divide-y divide-slate-800">
            <?php foreach ($data['recent_notices'] as $notice): ?>
                <div class="p-6 hover:bg-slate-800/30 transition-colors group cursor-pointer">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-bold text-white group-hover:text-blue-400 transition-colors"><?= e($notice['title']) ?></h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest border 
                            <?= $notice['priority'] === 'urgent' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : 
                                ($notice['priority'] === 'critical' ? 'bg-red-500/10 text-red-500 border-red-500/20' : 
                                'bg-slate-500/10 text-slate-400 border-slate-500/20') ?>">
                            <?= e($notice['priority']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($data['recent_notices'])): ?>
                <div class="p-12 text-center">
                    <p class="text-slate-500">No active operational bulletins.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sync Reminder -->
    <div class="p-8 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl text-white">
        <h3 class="text-xl font-bold mb-2">Sync your iPad</h3>
        <p class="text-blue-100 mb-6 max-w-lg">For the best experience, ensure your CrewAssist iPad app is synchronized. You can download the latest enterprise build from the Install section.</p>
        <a href="/install" class="inline-flex items-center px-6 py-3 bg-white text-blue-600 rounded-2xl font-bold hover:bg-blue-50 transition-colors">
            Get iPad Build
        </a>
    </div>
</div>
