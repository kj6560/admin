@extends('layout')
@section('content')
<div class="pagetitle">
    <h1>Websites</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active">Websites</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<section class="section">
    <div class="row">
        <div class="col-lg-12">

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title">Websites</h5>
                        </div>
                        <div class="col">
                            <a href="/addWebsite" class="btn btn-primary" style="float: right;margin: 20px;">Add Website</a>
                        </div>
                    </div>
                    <table class="table datatable">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Domain</th>
                                <th scope="col">Document Root</th>
                                <th scope="col">Directory</th>
                                <th scope="col">Created Date</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($websites as $website)
                            <tr>
                                <td>{{$website->name}}</td>
                                <td>{{$website->domain_name}}</td>
                                <td>{{$website->document_root}}</td>
                                <td>{{$website->directory}}</td>
                                <td>{{$website->created_at}}</td>
                                <td>
                                    <a href="/deleteWebsite/{{$website->id}}" class="btn btn-danger">Delete</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection