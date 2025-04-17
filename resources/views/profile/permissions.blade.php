<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Permissions') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium mb-4">Current Permissions</h3>
                    
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                                <th class="px-6 py-3 bg-gray-50"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($allPermissions->groupBy('group') as $group => $permissions)
                                @foreach($permissions as $permission)
                                    @if($user->permissions->contains($permission))
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $permission->display_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ucfirst($permission->group) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button 
                                                class="text-red-600 hover:text-red-900"
                                                onclick="removePermission({{ $permission->id }})"
                                            >Remove</button>
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <h3 class="text-lg font-medium mb-4">Add Permission</h3>
                    
                    <select id="permissionSelect" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="">Select Permission</option>
                        @foreach($unassignedPermissions as $permission)
                            <option value="{{ $permission->id }}">
                                {{ $permission->display_name }} ({{ ucfirst($permission->group) }})
                            </option>
                        @endforeach
                    </select>

                    <button 
                        onclick="addPermission()"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                    >
                        Add Permission
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function addPermission() {
            const permissionId = document.getElementById('permissionSelect').value;
            if (!permissionId) return;

            fetch('{{ route("profile.permissions.add") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ permission_id: permissionId })
            }).then(() => window.location.reload());
        }

        function removePermission(id) {
            if (!confirm('Are you sure?')) return;

            fetch(`/profile/permissions/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).then(() => window.location.reload());
        }
    </script>
    @endpush
</x-app-layout>
