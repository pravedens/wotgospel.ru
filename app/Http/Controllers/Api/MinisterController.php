<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MinisterCategory;
use Illuminate\Http\Request;

class MinisterController extends Controller
{
    public function index()
    {
        $ministers = User::role('minister')
         ->whereNotNull('email_verified_at')
         ->whereHas('ministerCategories')
         ->with(['socialLinks', 'fieldVisibilities', 'ministerCategories'])
         ->orderByMinisterPriority()
         ->get();
        
        $result = $ministers->map(fn($minister) => $this->formatMinisterForPublic($minister));
        
        return response()->json(['success' => true, 'ministers' => $result]);
    }
    
    public function show($id)
    {
        $minister = User::role('minister')
            ->whereHas('ministerCategories')
            ->with(['socialLinks', 'fieldVisibilities', 'ministerCategories'])
            ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'minister' => $this->formatMinisterForPublic($minister)
        ]);
    }
    
    public function categories()
    {
        $categories = MinisterCategory::ordered()->get();
        return response()->json(['success' => true, 'categories' => $categories]);
    }
    
    // ============ ЭТОТ МЕТОД ДОЛЖЕН БЫТЬ ТОЛЬКО ОДИН РАЗ! ============
    public function byCategory($slug)
    {
        $category = MinisterCategory::where('slug', $slug)->firstOrFail();
        
        $ministers = $category->users()
         ->role('minister')
         ->whereNotNull('email_verified_at')
         ->whereHas('ministerCategories')
         ->with(['socialLinks', 'fieldVisibilities', 'ministerCategories'])
         ->orderByMinisterPriority()
         ->get();
        $result = $ministers->map(fn($minister) => $this->formatMinisterForPublic($minister));
        
        return response()->json([
            'success' => true,
            'category' => $category,
            'ministers' => $result
        ]);
    }
    // ============ КОНЕЦ МЕТОДА ============
    
    private function formatMinisterForPublic(User $minister): array
    {
        $data = [
            'id' => $minister->id,
            'roles' => $minister->getRoleNames()->toArray(),
        ];
        
        $fields = ['name', 'last_name', 'middle_name', 'phone', 'city', 'church_name', 'about', 'birth_date'];
        
        $fullNameParts = [];
        if ($minister->isFieldVisible('last_name')) $fullNameParts[] = $minister->last_name;
        if ($minister->isFieldVisible('name')) $fullNameParts[] = $minister->name;
        if ($minister->isFieldVisible('middle_name')) $fullNameParts[] = $minister->middle_name;
        $data['full_name'] = implode(' ', $fullNameParts) ?: 'Служитель';
        
        foreach ($fields as $field) {
            if ($minister->isFieldVisible($field)) {
                if ($field === 'birth_date' && $minister->birth_date) {
                    $data[$field] = $minister->birth_date->format('Y-m-d');
                } else {
                    $data[$field] = $minister->$field;
                }
            }
        }
        
        if ($minister->isFieldVisible('email')) {
            $data['email'] = $minister->email;
        }
        
        if ($minister->isFieldVisible('avatar')) {
            $data['avatar_url'] = $minister->avatar_url;
        }
        
        $data['social_links'] = $minister->socialLinks;
        
        $data['minister_categories'] = $minister->ministerCategories->map(fn($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'slug' => $cat->slug,
            'icon' => $cat->icon,
            'color' => $cat->color,
        ]);
        
        return $data;
    }
}