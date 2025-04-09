@extends('backend.app')

@section('title', 'Add Breed')

@push('styles')
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/css/dropify.css">
    <style>
        .ck-editor__editable[role="textbox"] {
            min-height: 150px;
        }
        .dropify-wrapper .dropify-message p {
            font-size:35px !important;
        }
        #qb-toolbar-container{
            display : none !important;
        }
    </style>
@endpush
@section('content')


        <div class="page-content">
            <div class="container-fluid">

                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
                            <h4 class="mb-sm-0">Add Breed</h4>

                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('admin.breed.index') }}">Breed</a></li>
                                    <li class="breadcrumb-item active">Create</li>
                                </ol>
                            </div>

                        </div>
                    </div>
                </div>
                <!-- end page title -->



                    <div class="row">
                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0 flex-grow-1">Add Breed</h4>
                                </div><!-- end card header -->
                                <div class="card-body">
                                    <p class="text-muted">Add new breed information below.</p>
                                    <div class="live-preview">
                                        <form action="{{ route('admin.breed.store') }}" method="POST" enctype="multipart/form-data">
                                            @csrf

                                            <!-- Title Field -->
                                            <div class="mb-3">
                                                <label for="title" class="form-label">Title</label>
                                                <input type="text" class="form-control @error('title') is-invalid @enderror"
                                                       id="title" name="title" placeholder="Enter title" value="{{ old('title') }}">
                                                @error('title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Sub-title Field -->
                                            <div class="mb-3">
                                                <label for="sub_title" class="form-label">Sub Title</label>
                                                <textarea class="form-control @error('sub_title') is-invalid @enderror" id="sub_title" name="sub_title" rows="3">{{ old('sub_title') }}</textarea>
                                                @error('sub_title')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Image Upload Field -->
                                            <div class="mb-3">
                                                <label for="image" class="form-label">Image</label>
                                                <input type="file" class="dropify form-control @error('image') is-invalid @enderror"
                                                       name="image" id="image">
                                                @error('image')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Content Field -->
                                            <div class="mb-3">
                                                <label for="content" class="form-label">Content</label>
                                                <textarea class="form-control @error('content') is-invalid @enderror"
                                                          id="content" name="content" rows="3">{{ old('content') }}</textarea>
                                                @error('content')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <!-- Type Radio Buttons -->
                                            <div class="mb-3">
                                                <label class="form-label">Type</label>
                                                <div class="form-check form-radio-primary">
                                                    <input class="form-check-input @error('type') is-invalid @enderror"
                                                           type="radio" name="type" id="dog" value="dog" {{ old('type') == 'dog' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="dog">Dog</label>
                                                </div>
                                                <div class="form-check form-radio-primary">
                                                    <input class="form-check-input @error('type') is-invalid @enderror"
                                                           type="radio" name="type" id="cat" value="cat" {{ old('type') == 'cat' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="cat">Cat</label>
                                                </div>
                                                @error('type')
                                                <div class="invalid-feedback d-block">{{ $message }}</div> <!-- d-block to show for radio buttons -->
                                                @enderror
                                            </div>

                                            <!-- Submit Button -->
                                            <button type="submit" class="btn btn-primary">Submit</button>
                                        </form>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

            </div> <!-- container-fluid -->
        </div>
        <!-- End Page-content -->

@endsection

@push('scripts')
    {{--== SWEET ALERT ==--}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/ckeditor5/41.3.1/ckeditor.min.js"></script>

    <script type="text/javascript" src="https://jeremyfagis.github.io/dropify/dist/js/dropify.min.js"></script>



    <script>
        ClassicEditor
            .create(document.querySelector('#content'))
            .then(editor => {
                console.log('Editor was initialized', editor);
            })
            .catch(error => {
                console.error(error.stack);
            });


        $('.dropify').dropify();

    </script>
@endpush
