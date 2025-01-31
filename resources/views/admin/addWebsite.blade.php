@extends('layout')
@section('content')
<div class="pagetitle">
    <h1>Dashboard</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<section class="section">
    <div class="row">
        <div class="col-lg-12">

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Create Website</h5>
                    <form action="/storeWebsite" method="post">
                        @csrf
                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">Website Name</label>
                            <div class="col-sm-10">
                                <input type="text" name="name" class="form-control" id="inputText">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">Domain Name</label>
                            <div class="col-sm-10">
                                <input type="text" name="domain_name" class="form-control" id="inputText">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">Document Root</label>
                            <div class="col-sm-10">
                                <input type="text" name="document_root" class="form-control" id="inputText">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">Server Name</label>
                            <div class="col-sm-10">
                                <input type="text" name="server_name" class="form-control" id="inputText">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">Server Alias</label>
                            <div class="col-sm-10">
                                <input type="text" name="server_alias" class="form-control" id="inputText">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">Directory</label>
                            <div class="col-sm-10">
                                <input type="text" name="directory" class="form-control" id="inputText">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">Port</label>
                            <div class="col-sm-10">
                                <input type="text" name="port" class="form-control" id="inputText">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-sm-2 col-form-label">Status</label>
                            <div class="col-sm-10">
                                <select class="form-select" name="status" aria-label="Default select example">
                                    <option value="0">Select Status</option>
                                    <option value="1">Active</option>
                                    <option value="2">InActive</option>
                                </select>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form><!-- End Horizontal Form -->

                </div>
            </div>

        </div>
    </div>
</section>

@endsection