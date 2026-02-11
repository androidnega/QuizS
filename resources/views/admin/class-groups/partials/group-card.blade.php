<div class="group rounded-lg border border-slate-200 bg-white p-3 hover:border-slate-300 hover:bg-slate-50/50 transition-colors text-left flex flex-col min-w-0">
    <div class="flex items-start justify-between gap-2">
        <a href="{{ route('dashboard.class-groups.show', $g) }}" class="flex-1 min-w-0">
            <h3 class="text-sm font-semibold text-gray-900 truncate group-hover:text-primary-600" title="{{ $g->name }}">{{ $g->name }}</h3>
        </a>
        <div class="flex items-center gap-0.5 shrink-0" onclick="event.stopPropagation();">
            <a href="{{ route('dashboard.class-groups.show', $g) }}" class="p-1 rounded text-gray-400 hover:text-primary-600 hover:bg-primary-50" title="View"><i class="fas fa-eye text-xs"></i></a>
            <a href="{{ route('dashboard.class-groups.edit', $g) }}" class="p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100" title="Edit"><i class="fas fa-pen text-xs"></i></a>
            <form action="{{ route('dashboard.class-groups.destroy', $g) }}" method="post" class="inline" onsubmit="return confirm('Delete class group \'{{ addslashes($g->name) }}\'?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-1 rounded text-gray-400 hover:text-danger-600 hover:bg-danger-50" title="Delete"><i class="fas fa-trash-alt text-xs"></i></button>
            </form>
        </div>
    </div>
    <a href="{{ route('dashboard.class-groups.show', $g) }}" class="mt-2 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-500">
        <span>{{ $g->students_count ?? 0 }} students</span>
        <span>{{ $g->courses_count ?? 0 }} courses</span>
        <span>{{ $g->quizzes_count ?? 0 }} quizzes</span>
    </a>
</div>
