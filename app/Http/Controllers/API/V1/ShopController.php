<?php

namespace App\Http\Controllers\API\V1;

use App\Models\Shop;
use App\Models\Image;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Support\Facades\Validator;


class ShopController extends Controller
{

    public function __construct()
    {
        $this->middleware("auth:sanctum")->except(["index", "show", "sortByDepartments"]);
        
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (Shop::count() === 0) {
            return response()->json(['message' => 'Aucun commerçant trouvé'], 404);
        }

        return response()->json(['message' => 'Commerçants trouvé', 'Commerçants' => Shop::latest()->get()], 200);
    }

    public function getShopByCategorie(){
        if (Shop::count() === 0) {
            return response()->json(['message' => 'Aucun commerçant trouvé'], 404);
            
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // a compléter 
        $validator = Validator::make(
            $request->all(),
            [
                "shop_title" => 'required|string|max:100',
                "adresse" => 'required|string|max:100',
                'description' => 'required|string',
                "website" => 'required',
                "phone_number" =>  'required',
                "zip_code" => 'required|max:5',
                "city" => 'required|max:150',
                "rating" => 'nullable',
                "longitude" => 'required',
                "latitude" => 'required',
                "department_id" => 'required',
                "category_id" => 'required',
                'image.*' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
            ]
        );
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();

        $shop = Shop::create(
            [
                "shop_title" => $request->shop_title,
                "adresse" => $request->adresse,
                'description' => $request->description,
                "website" => $request->website,
                "phone_number" =>  $request->phone_number,
                "zip_code" => $request->zip_code,
                "city" => $request->city,
                "rating" => $request->rating,
                "longitude" => $request->longitude,
                "latitude" => $request->latitude,
                "shop_status" => $user->role->role_name === "admin" ? 1 : 0,
                "user_id" => $user->id,
                "department_id" => $request->department_id,
                "category_id" => $request->category_id,
            ]
        );

        if(count($request->products_id) > 0){
            
            foreach($request->products_id as $product_id){
                DB::table("shops_products")->insert([
                    'shop_id' => $shop->id ,
                    'product_id' => $product_id
                ]);
            }
        }
        $shopImage = "";
        if ($request->hasFile('image')){
            //dd($request->image);
            $shopImage = UploadImage($request->image, Auth::user()->id ,$shop->id );
        }else{
            Image::create([
                "image_name" => "default_shop.jpg",
                "image_status" => Auth::user()->role->role_name === "admin"  ? 1 : 0,
                "user_id" => Auth::user()->id,
                "shop_id" =>  $shop->id,
                "recipe_id" => null,
                "is_profil" =>null
            ]);
        }
        return response()->json(['message' => 'Le commerçants a été ajouté ', 'Commerçants' => $shop ,$shopImage ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Shop $shop)
    {
        if (!$shop) {
            return response()->json(['message' => 'Aucun commerçant trouvé'], 404);
        }


        return response()->json(['message' => 'Commerçant trouvé', 'shop' => $shop], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shop $shop)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "shop_title" => 'nullable|string|max:100',
                'description' => 'nullable|string',
                "website" => 'nullable',
                "phone_number" =>  'nullable',
                "zip_code" => 'nullable|max:5',
                "city" => 'nullable|max:150',
                "rating" => 'nullable',
                "longitude" => 'nullable',
                "latitude" => 'nullable',
                "department_id" => 'nullable',
                "category_id" => 'nullable',
            ]
        );
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();

        $shop->update(
            [
                "shop_title" => $request->shop_title,
                'description' => $request->description,
                "website" => $request->website,
                "phone_number" =>  $request->phone_number,
                "zip_code" => $request->zip_code,
                "city" => $request->city,
                "rating" => $request->rating,
                "longitude" => $request->longitude,
                "latitude" => $request->latitude,
                "shop_status" => $user->role->role_name === "admin" ? 1 : 0,
                "department_id" => $request->department_id,
                "category_id" => $request->category_id,
            ]
        );

        $shop->products()->detach();
        $shop->products()->attach($request->products_id);

        return response()->json(['message' => 'Le commerçant a été modifié ', 'Commerçant' => $shop], 200);
    }

    public function sortShops()
    {
        $sorted_shops = QueryBuilder::for(Shop::class)->allowedFilters(["department_id", "category_id", "shop_title", "products"])->get();
        return response()->json(['message' => 'Commerçants trouvé', 'Commerçants' => $sorted_shops], 200);
    }

    public function getShopByUserId(){

        $userShops = Shop::where("user_id","=", auth::user()->id)->latest()->get();

        if (count($userShops) === 0) {
            return response()->json(['message' => 'Aucun commerçant trouvé'], 404);
        }

        return response()->json(['message' => 'Commerçants trouvé', 'Commerçants' => $userShops], 200);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shop $shop)
    {
        if (!$shop) {
            return response()->json(['message' => 'Aucun commerçant trouvé'], 404);
        }

        Shop::destroy($shop->id);
        return response()->json(['message' => 'Le commerçants a été supprimé '], 200);
    }
}
