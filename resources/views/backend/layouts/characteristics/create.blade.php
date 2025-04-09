@extends('backend.app')

@section('title', 'Add Characteristics')

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
                        <h4 class="mb-sm-0">Add Characteristics</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('admin.breed.index') }}">Breed</a></li>
                                <li class="breadcrumb-item active">Add Characteristics</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <form id="breed-characteristic-form" action="{{ route('admin.breed.characteristic.createOrUpdate') }}" method="POST" enctype="multipart/form-data">
            @csrf

                <div class="row">
                    <div class="col-xxl-12">
                        <div class="card">
                            <div class="card-header align-items-center d-flex">
                                <h4 class="card-title mb-0 flex-grow-1">Select Breed</h4>
                            </div>
                            <div class="card-body">
                                <select name="breed" id="breed-select" class="form-select mb-3">
                                    <option value="">Select breed's name</option>
                                    @foreach($breeds as $breed)
                                        <option value="{{ $breed->id }}"
                                            {{ old('breed') == $breed->id ? 'selected' : '' }}>
                                            {{ $breed->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('breed')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $sections = [
                        "Physical Characteristics" => "physical_characteristics",
                        "Behavior and Temperament" => "behavior_and_temperament",
                        "Food and Diet" => "food_and_diet",
                        "Health Conditions" => "health_conditions",
                        "Lifespan" => "lifespan",
                        "Grooming" => "grooming",
                        "Conclusion" => "conclusion"
                    ];
                @endphp

                <div class="row" id="characteristics-row">

                </div>

                <div class="row">
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-body">
                                <input type="submit" class="btn btn-primary" value="Submit">
                            </div>
                        </div>
                    </div>
                </div>
            </form>





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
        document.addEventListener("DOMContentLoaded", function () {
            // Initialize CKEditor for all textareas
            document.querySelectorAll("textarea").forEach(textarea => {
                ClassicEditor
                    .create(textarea)
                    .then(editor => {
                        console.log('Editor initialized for:', textarea);
                    })
                    .catch(error => {
                        console.error('CKEditor Error:', error.stack);
                    });
            });

            // Initialize Dropify for all file inputs
            $('.dropify').dropify();
        });
    </script>

    <script>
        $(document).ready(function () {
            function fetchCharacteristics(breedId) {
                if (!breedId) {
                    $('#characteristics-row').empty(); // Clear previous data
                    return;
                }

                $.ajax({
                    url: "{{ route('admin.breed.characteristic.fetch') }}",
                    type: "GET",
                    data: { breed_id: breedId },
                    success: function (response) {
                        if (response.success) {
                            let characteristics = response.characteristics;
                            let characteristicsRow = $('#characteristics-row');

                            characteristicsRow.empty(); // Clear existing data
                            console.log(characteristics);

                            $.each(characteristics, function (name, data) {
                                let title = name.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
                                let content = (data && data.content) ? data.content : '';
                                let image = (data && data.image) ? `{{ asset('${data.image}') }}` : '';


                                // Generate column HTML
                                let columnHtml = `
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-header align-items-center d-flex">
                                    <h4 class="card-title mb-0 flex-grow-1">${title}</h4>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Information for ${title.toLowerCase()}.</p>
                                    <div class="live-preview">
                                        <!-- Image Upload Field -->
                                        <div class="mb-3">
                                            <label for="${name}_image" class="form-label">Image</label>
                                            <input type="file" class="dropify form-control"
                                                   name="${name}_image" id="${name}_image"
                                                   data-default-file="${image}">
                                            <div class="invalid-feedback"></div>
                                        </div>

                                        <!-- Content Field -->
                                        <div class="mb-3">
                                            <label for="${name}_content" class="form-label">Content</label>
                                            <textarea class="form-control"
                                                      id="${name}_content" name="${name}_content" rows="3">${content}</textarea>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                                characteristicsRow.append(columnHtml);
                            });

                            // Reinitialize Dropify & CKEditor for dynamically added elements
                            $('.dropify').dropify();
                            document.querySelectorAll("textarea").forEach(textarea => {
                                ClassicEditor
                                    .create(textarea)
                                    .catch(error => console.error('CKEditor Error:', error.stack));

                            });
                        }
                    }
                });
            }

            /*function clearFields() {
                @foreach ($sections as $title => $name)
                $('#{{ $name }}_content').val('');
                @endforeach
            }*/

            // On breed change, fetch characteristics
            $('#breed-select').change(function () {
                var breedId = $(this).val();
                fetchCharacteristics(breedId);
            });

            // Load old values if available (for form validation errors)
            var selectedBreed = $('#breed-select').val();
            if (selectedBreed) {
                fetchCharacteristics(selectedBreed);
            }




            /* ================================== SUBMIT THE FORM =========================*/
            $('#breed-characteristic-form').submit(function (e) {
                e.preventDefault(); // Prevent default form submission

                document.querySelectorAll("textarea").forEach(textarea => {
                    let mb3Element = textarea.closest('.mb-3');
                    let ckContent = mb3Element.querySelector('.ck-content');
                    if (ckContent) {
                        textarea.value = ckContent.innerHTML;
                    }
                })

                let formData = new FormData(this);
// Log the form data
                formData.forEach((value, key) => {
                    console.log(key, value);
                });
                $.ajax({
                    url: "{{ route('admin.breed.characteristic.createOrUpdate') }}",
                    type: "POST",
                    data: formData,
                    processData: false,  // Required for FormData
                    contentType: false,  // Required for FormData
                    beforeSend: function () {
                        $('.invalid-feedback').text('').hide(); // Clear previous errors
                        $('.is-invalid').removeClass('is-invalid'); // Remove red borders
                    },
                    success: function (response) {
                        Swal.fire({
                            icon: "success",
                            title: "Success!",
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });


                        // Reinitialize CKEditor (Optional)
                        /*document.querySelectorAll("textarea").forEach(textarea => {
                            ClassicEditor
                                .create(textarea)
                                .catch(error => console.error('CKEditor Error:', error.stack));
                        });*/
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) { // Laravel validation error
                            let errors = xhr.responseJSON.errors;
                            $.each(errors, function (field, messages) {
                                let inputField = $('[name="' + field + '"]');
                                inputField.addClass('is-invalid'); // Add red border
                                inputField.closest('.mb-3').find('.invalid-feedback').text(messages[0]).show();
                            });
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Error!",
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    }
                });
            });
        });
    </script>

@endpush
