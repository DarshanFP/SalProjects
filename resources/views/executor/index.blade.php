@extends('executor.dashboard')

@section('content')
<div class="container">
    <h1>Executor Dashboard</h1>

    <!-- Include the projects index view -->
    @include('projects.Oldprojects.index')
</div>
@endsection
