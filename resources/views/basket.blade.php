@if(Basket::contents())
    <form action="{{route('ecommerce.basket.update')}}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <table class="table table-bordered">
            <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col"></th>
                <th scope="col">Count</th>
                <th scope="col"></th>
                <th scope="col">Price</th>
                <th scope="col">Tax</th>
                <th scope="col">Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach(Basket::contents() as $index => $item)
                <tr>
                    <td><a href="">{{$item->name}}</a>
                        @if( $item->hasOptions())
                            <br/><br/> Tilvalg <br/>
                            @foreach($item->options as $option)
                                {{$option['name']}} = {{$option['value']}} ({{$option['price']}})<br/>
                            @endforeach
                        @endif
                    </td>
                    <td><a href="{{route('ecommerce.basket.item.inc',[$index])}}">+</a></td>
                    <td><input class="form-control" type="text" name="quantity[{{$index}}]" value="{{$item->quantity}}" style="width:90px"/></td>
                    <td><a href="{{route('ecommerce.basket.item.dec',[$index])}}">-</a></td>
                    <td>{{$item->single(false)}}</td>
                    <td>{{$item->tax}}</td>
                    <td>{{$item->total(false)}}</td>
                    <th scope="col"><a href="{{route('ecommerce.basket.item.remove',[$index])}}"><i class="fas fa-trash"></i></a></th>
                </tr>
            @endforeach
            </tbody>
            <tr>
                <td colspan="6">{{ trans('ecommerce::messages.price') }}</td>
                <td>{{ Basket::total(false)}}</td>
            </tr>
            <tr>
                <td colspan="6">{{ trans('ecommerce::messages.tax') }}</td>
                <td>{{ Basket::tax()}}</td>
            </tr>
            <tr>
                <td colspan="6">{{ trans('ecommerce::messages.total') }}</td>
                <td>{{ Basket::total()}}</td>
            </tr>
        </table>
        <input type="submit" value="{{ trans('ecommerce::messages.update') }}" class="btn btn-success"> <a href="{{route('ecommerce.basket.destroy')}}" class="btn btn-danger">{{ trans('ecommerce::messages.empty') }}</a>
    </form>
@else
    Basket empty try add some demo <a href="{{route('ecommerce.basket.demo')}}">data</a>
@endif
