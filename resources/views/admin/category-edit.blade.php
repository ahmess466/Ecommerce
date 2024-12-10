@extends('layouts.admin')
@section('content')
<div class="main-content-inner">
    <div class="main-content-wrap">
        <div class="flex items-center flex-wrap justify-between gap20 mb-27">
            <h3>Category Information</h3>
            <ul class="breadcrumbs flex items-center flex-wrap justify-start gap10">
                <li>
                    <a href="#">
                        <div class="text-tiny">Dashboard</div>
                    </a>
                </li>
                <li>
                    <i class="icon-chevron-right"></i>
                </li>
                <li>
                    <a href="#">
                        <div class="text-tiny">Categories</div>
                    </a>
                </li>
                <li>
                    <i class="icon-chevron-right"></i>
                </li>
                <li>
                    <div class="text-tiny">Edit category</div>
                </li>
            </ul>
        </div>

        <!-- New category Form -->
        <div class="wg-box">
            <form action="{{ route('admin.categories.update', $category->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type = 'hidden' name = 'id' value = {{$category->id}}/>
                <!-- category Name Field -->
                <fieldset class="name">
                    <div class="body-title">category Name <span class="tf-color-1">*</span></div>
                    <input class="flex-grow" type="text" placeholder="category Name" name="name" tabindex="0" value="{{$category->name}}" aria-required="true" required="">
                </fieldset>
                @error('name')
                <span class="alert alert-danger text-center">{{ $message }}</span>
                @enderror

                <!-- category Slug Field -->
                <fieldset class="name">
                    <div class="body-title">category Slug <span class="tf-color-1">*</span></div>
                    <input class="flex-grow" type="text" placeholder="category Slug" name="slug" tabindex="0" value="{{$category->slug}}" aria-required="true" required="">
                </fieldset>
                @error('slug')
                <span class="alert alert-danger text-center">{{ $message }}</span>
                @enderror

                <!-- Image Upload Field -->
                <fieldset>
                    <div class="body-title">Upload Images <span class="tf-color-1">*</span></div>
                    <div class="upload-image flex-grow">
                        @if ($category -> image)


                        <div class="item" id="imgpreview" >
                            <img src="{{asset('uploads/categories')}}/{{$category->image}}" class="effect8" alt="">
                        </div>
                        @endif
                        <div id="upload-file" class="item up-load">
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
                @error('image')
                <span class="alert alert-danger text-center">{{ $message }}</span>
                @enderror

                <!-- Save Button -->
                <div class="bot">
                    <div></div>
                    <button class="tf-button w208" type="submit">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
