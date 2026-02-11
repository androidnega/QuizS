<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quizzes</th>
            @if(isset($isSuperAdmin) && $isSuperAdmin)
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Examiners</th>
            @endif
            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @foreach($courses as $c)
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">{{ $c->code ?? '—' }}</td>
                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $c->name }}</td>
                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-600">{{ $c->quizzes_count ?? 0 }}</td>
                @if(isset($isSuperAdmin) && $isSuperAdmin)
                <td class="px-3 py-2 text-sm text-gray-600 max-w-[180px] truncate" title="{{ $c->examiners->isNotEmpty() ? $c->examiners->pluck('username')->join(', ') : '—' }}">
                    {{ $c->examiners->isNotEmpty() ? $c->examiners->pluck('username')->join(', ') : '—' }}
                </td>
                @endif
                <td class="px-3 py-2 whitespace-nowrap">
                    @if($c->is_archived)
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Archived</span>
                    @else
                        <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-success-100 text-success-800">Active</span>
                    @endif
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-right text-sm">
                    <a href="{{ route('dashboard.courses.edit', $c) }}" class="text-primary-600 hover:text-primary-900 mr-2">Edit</a>
                    @if($c->is_archived)
                        <form action="{{ route('dashboard.courses.unarchive', $c) }}" method="post" class="inline mr-2">
                            @csrf
                            <button type="submit" class="text-success-600 hover:text-success-800">Restore</button>
                        </form>
                    @else
                        <form action="{{ route('dashboard.courses.archive', $c) }}" method="post" class="inline mr-2" onsubmit="return confirm('Archive this course?');">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-gray-800">Archive</button>
                        </form>
                    @endif
                    @if(isset($isSuperAdmin) && $isSuperAdmin)
                    <form action="{{ route('dashboard.courses.destroy', $c) }}" method="post" class="inline" onsubmit="return confirm('Permanently delete course \'{{ addslashes($c->name) }}\'? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-danger-600 hover:text-danger-800" title="Delete course">Delete</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
