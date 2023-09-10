<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except('store');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (User::count() === 0) {
            return response()->json(['message' => 'Pas de utilisateurs trouvé'], 404);
        }

        return response()->json(['message' => 'Utilisateurs trouvé', 'Utilisateurs' => User::latest()->paginate()], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                "user_name" =>  'required|max:100',
                'email' => 'required|email|unique:users,email',
                'image' => 'image|mimes:jpg,jpeg,png,svg|max:2048|nullable',
                'password' => 'required', Password::min(8)->letters()->mixedCase()->numbers(),
            ]
        );

        if ($validator->fails()) {

            return response()->json(
                [
                    ['errors' => $validator->errors()]
                ],
                422
            );
        }
        // ajouter le helper poour la sauvgarde d'images
        $user = User::create([
            "user_name" =>  $request->user_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if($request->has('image')){
            UploadImage($request->image ,$user->id);
        }

        return response()->json(['message' => 'L\'utilisateur a été ajouté ', 'Utilisateur' => $user], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        if (!$user) {
            return response()->json(['message' => 'L\'utilisateur pas trouvé'], 401);
        }

        return response()->json(['message' => 'L\'utilisateur trouvé', 'Utilisateur' => $user], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make(
            $request->all(),
            [
                "user_name" => 'required|max:25',
                "email"     => 'required|max:30',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }  

        $user->update($request->all());

        return response()->json(['message' => 'L\'utilisateur a été modifier', 'user' => $user], 200);
    }

    

    public function updatepassword(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'new_password' => ['required','confirmed', Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if (Hash::check($request->password, $user->password)) {
            if ($request->password !== $request->new_password) {
                    $user->password = Hash::make($request->new_password);
                    $user->save();
                    return response()->json(['message' => 'Votre mot de passe a bien été modifié'], 200); 
            } else {
                return response()->json(['message' => 'Votre nouveau mot de passe est identique avec ancien'], 200);
            }
        } else {
            return response()->json(['message' => 'Votre mot de passe actuel ne correspond pas ! '], 200);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if (!$user) {
            return response()->json(['message' => 'Pas d\'utilisateur trouvé'], 404);
        }

        User::destroy($user->id);
        return response()->json(['message' => 'L\'utilisateur a été supprimé'], 200);
    }
}
