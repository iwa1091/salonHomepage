@extends('layout.app')

@section('title', $product->name . ' 詳細ページ')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/products_show.css') }}">
@endsection

@section('content')
    <div class="all-contents">
        <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="top-contents">
                <div class="left-content">
                    <p><span class="span-item">商品一覧></span>{{$product->name}}</p>
                    <output id="list" class="img-content">
                        @if($product->image_path)
                            <img id="existing_image" class="reader_image" src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" />
                        @endif
                    </output>
                </div>
                <div class="right-content">
                    <label class="name-label">商品名</label>
                    <input type="text" value="{{$product->name}}" name="name" class="text">
                    @error('name')
                    <span class="input_error">
                        <p class="input_error_message">{{$errors->first('name')}}</p>
                    </span>
                    @enderror
                    <label class="price-label">値段</label>
                    <input type="number" value="{{$product->price}}" name="price" class="text">
                    @error('price')
                    <span class="input_error">
                        <p class="input_error_message">{{$errors->first('price')}}</p>
                    </span>
                    @enderror
                </div>
            </div>
            <div class="under-content">
                <label for="product_image" class="image-label">新しい商品画像</label>
                <input type="file" id="product_image" class="image" name="image">
                @error('image')
                <span class="input_error">
                    <p class="input_error_message">{{$errors->first('image')}}</p>
                </span>
                @enderror
                <label class="description-label">商品説明</label>
                <textarea cols="30" rows="5" name="description" class="product-description">{{$product->description}}</textarea>
                @error('description')
                <span class="input_error">
                    <p class="input_error_message">{{$errors->first('description')}}</p>
                </span>
                @enderror
                <div class="button-content">
                    <a href="{{ route('products.index') }}" class="back">戻る</a>
                    <button type="submit" class="button-change">変更を保存</button>
                    <div class="trash-can-content">
                        <form action="{{ route('products.destroy', $product->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="trash-can-button" onclick="return confirm('本当にこの商品を削除しますか？');">
                                <img src="{{ asset('/img/trash-can.png') }}" alt="ゴミ箱の画像" class="img-trash-can" />
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        document.getElementById('product_image').onchange = function(event) {
            var files = event.target.files;
            var list = document.getElementById('list');
            var existingImage = document.getElementById('existing_image');

            // 既存の画像を非表示にする
            if (existingImage) {
                existingImage.style.display = 'none';
            }
            
            // 既存のプレビュー画像をクリアする
            list.innerHTML = '';

            if (files.length > 0) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = document.createElement('img');
                    img.className = 'reader_image';
                    img.src = e.target.result;
                    list.appendChild(img);
                };
                reader.readAsDataURL(files[0]);
            } else {
                // ファイルが選択されていない場合は既存の画像を再表示
                if (existingImage) {
                    existingImage.style.display = 'block';
                }
            }
        };
    </script>
@endsection
