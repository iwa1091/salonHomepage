@extends('layout.app')

@section('title', '商品登録ページ')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/products_create.css') }}" />
@endsection

@section('content')
    <main class="main-contents">
        <h1>商品登録</h1>
        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
        @csrf
            <label class="label">商品名<span class="require">必須</span></label>
            <input type="text" placeholder="商品名を入力" name="name" class="text" value="{{ old('name') }}">
            @error('name')
                <span class="input_error">
                    <p class="input_error_message">{{$errors->first('name')}}</p>
                </span>
            @enderror
            <label class="label">値段<span class="require">必須</span></label>
            <input type="number" class="text" placeholder="値段を入力" name="price" value="{{ old('price') }}">
            @error('price')
                <span class="input_error">
                    <p class="input_error_message">{{$errors->first('price')}}</p>
                </span>
            @enderror
            <label class="label">商品画像<span class="require">必須</span></label>
            <output id="list" class="image_output"></output>
            <input type="file" id="product_image" class="image" name="image">
            @error('image')
                <span class="input_error">
                    <p class="input_error_message">{{$errors->first('image')}}</p>
                </span>
            @enderror
            <label class="label">商品説明<span class="require">必須</span></label>
            <textarea cols="30" rows="5" placeholder="商品の説明を入力" name="description" class="textarea">{{ old('description') }}</textarea>
            @error('description')
                <span class="input_error">
                    <p class="input_error_message">{{$errors->first('description')}}</p>
                </span>
            @enderror
            <div class="button-content">
                <a href="{{ route('products.index') }}" class="back">戻る</a>
                <button type="submit" class="button-register">登録</button>
            </div>
        </form>
    </main>
@endsection

@section('scripts')
    <script>
        document.getElementById('product_image').onchange = function(event){
            initializeFiles();
            var files = event.target.files;

            for (var i = 0, f; f = files[i]; i++) {
                var reader = new FileReader();
                reader.readAsDataURL(f);
                reader.onload = (function(theFile) {
                    return function (e) {
                        var div = document.createElement('div');
                        div.className = 'reader_file';
                        div.innerHTML += '<img class="reader_image" src="' + e.target.result + '" />';
                        document.getElementById('list').insertBefore(div, null);
                    }
                })(f);
            }
        };

        function initializeFiles() {
            document.getElementById('list').innerHTML = '';
        }
    </script>
@endsection
