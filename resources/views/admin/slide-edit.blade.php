@extends('layouts.admin')

@section('content')
<div class="main-content-inner">
    <!-- main-content-wrap -->
    <div class="main-content-wrap">
        <div class="flex items-center flex-wrap justify-between gap20 mb-27">
            <h3>Slide</h3>
            <ul class="breadcrumbs flex items-center flex-wrap justify-start gap10">
                <li>
                    <a href="{{ route('admin.index') }}">
                        <div class="text-tiny">Dashboard</div>
                    </a>
                </li>
                <li>
                    <i class="icon-chevron-right"></i>
                </li>
                <li>
                    <a href="{{route('admin.slides')}}">
                        <div class="text-tiny">Slides</div>
                    </a>
                </li>
                <li>
                    <i class="icon-chevron-right"></i>
                </li>
                <li>
                    <div class="text-tiny">Edit Slide</div>
                </li>
            </ul>
        </div>
        <!-- new-category -->
        <div class="wg-box">
            <form method="POST" action="{{ route('admin.slide.update', ['id' => $slide->id]) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <fieldset class="name">
                    <div class="body-title">Tagline <span class="tf-color-1">*</span></div>
                    <input class="flex-grow" type="text" placeholder="Tagline" name="tagline" tabindex="0" value="{{ $slide->tagline }}" aria-required="true" required="">
                </fieldset>
                @error('tagline')  <span class="alert alert-danger text-center">{{$message}}</span>              @enderror
                <fieldset class="name">
                    <div class="body-title"> Title <span class="tf-color-1">*</span></div>
                    <input class="flex-grow" type="text" placeholder="Title" name="title" tabindex="0" value="{{ $slide->title }}" aria-required="true" required="">
                </fieldset>
                @error('title')  <span class="alert alert-danger text-center">{{$message}}</span>              @enderror
                <fieldset class="name">
                    <div class="body-title">Subtitle <span class="tf-color-1">*</span></div>
                    <input class="flex-grow" type="text" placeholder="Subtitle" name="subtitle" tabindex="0" value="{{ $slide->subtitle }}" aria-required="true" required="">
                </fieldset>
                @error('subtitle')  <span class="alert alert-danger text-center">{{$message}}</span>              @enderror
                <fieldset class="name">
                    <div class="body-title">Link <span class="tf-color-1">*</span></div>
                    <input class="flex-grow" type="text" placeholder="Link" name="link" tabindex="0" value="{{ $slide->link }}" aria-required="true" required="">
                    @error('link')  <span class="alert alert-danger text-center">{{$message}}</span>              @enderror
                </fieldset>
                <fieldset>
                    <div class="body-title">Upload images <span class="tf-color-1">*</span></div>
                    <div class="upload-image flex-grow">
                        @if($slide->image)
                        <div class="item" id="imgpreview_img" >
                            <img src="{{asset('uploads/slides')}}/{{$slide->image}}" alt="" class="effect8" id="imgPreview" />
                        </div>
                        @endif
                        <div class="item up-load">
                            <label class="uploadfile" for="myFile">
                                <span class="icon">
                                    <i class="icon-upload-cloud"></i>
                                </span>
                                <span class="body-text">Drop your images here or select <span class="tf-color">click to browse</span></span>
                                <input type="file" id="myFile" name="image" accept="image/*">
                            </label>
                        </div>
                    </div>
                </fieldset>
                @error('image')  <span class="alert alert-danger text-center">{{$message}}</span>              @enderror
                <fieldset class="category">
                    <div class="body-title">Status</div>
                    <div class="select flex-grow">
                        <select class="" name="status">
                            <option value="">Select</option>
                            <option value="1" @if($slide->status=='1') selected @endif>Active</option>
                            <option value="0" @if($slide->status=='0') selected @endif>Inactive</option>
                        </select>
                    </div>
                </fieldset>
                @error('status')  <span class="alert alert-danger text-center">{{$message}}</span>              @enderror
                <div class="bot">
                    <div></div>
                    <button class="tf-button w208" type="submit">Edit</button>
                </div>
            </form>
        </div>
        <!-- /new-category -->
    </div>
    <!-- /main-content-wrap -->
</div>

@endsection

@push('scripts')
<script>
$(function() {
    $("#myFile").on("change", function(e) {
        const file = this.files[0];
        if (file) {
            const imgPreview = $("#imgPreview");
            imgPreview.attr("src", URL.createObjectURL(file));
            $("#imgpreview_img").show();
        }
    });
});
</script>
@endpush