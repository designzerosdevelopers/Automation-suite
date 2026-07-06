@extends('layouts.app')

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <i class='subheader-icon fal fa-user-cog'></i> User Management
    </h1>
</div>

<div class="row">
    <div class="col-xl-12">
        <div id="panel-1" class="panel">
            <div class="panel-hdr">
                <h2>All Users</h2>
            </div>
            <div class="panel-container show">
                <div class="panel-content">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped w-100">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->is_approved)
                                            <span class="badge badge-success">Approved</span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(!$user->is_approved)
                                            <form action="{{ route('admin.approve', $user) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-success btn-sm">Approve ✅</button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.unapprove', $user) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-danger btn-sm">Unapprove ❌</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection