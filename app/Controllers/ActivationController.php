<?php
/**
 * ActivationController - handles invitation tokens for new users setting up their accounts
 */
class ActivationController {
    public function show(): void {
        $tokenStr = $_GET['token'] ?? '';
        if (!$tokenStr) {
            flash('error', 'No activation token provided.');
            redirect('/login');
        }
        
        $token = Database::fetch("SELECT * FROM invitation_tokens WHERE token = ? AND accepted_at IS NULL", [$tokenStr]);
        if (!$token || strtotime($token['expires_at']) < time()) {
            flash('error', 'Invalid or expired activation link.');
            redirect('/login');
        }
        
        $tenant = Tenant::find($token['tenant_id']);
        require VIEWS_PATH . '/auth/activate.php';
    }
    
    public function process(): void {
        if (!verifyCsrf()) {
            flash('error', 'Invalid form submission.');
            redirect('/login');
        }
        
        $tokenStr = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($password) || strlen($password) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            redirect("/activate?token=$tokenStr");
        }
        
        $token = Database::fetch("SELECT * FROM invitation_tokens WHERE token = ? AND accepted_at IS NULL", [$tokenStr]);
        if (!$token || strtotime($token['expires_at']) < time()) {
            flash('error', 'Invalid or expired activation link.');
            redirect('/login');
        }
        
        // Find existing user or create a new one
        $existing = UserModel::findByEmail($token['email'], $token['tenant_id']);
        if ($existing) {
            UserModel::update($existing['id'], ['password' => $password, 'status' => 'active']);
            $userId = $existing['id'];
        } else {
            $userId = UserModel::create([
                'tenant_id' => $token['tenant_id'],
                'name'      => $token['name'] ?? 'Invited User',
                'email'     => $token['email'],
                'password'  => $password,
                'status'    => 'active',
            ]);
            
            // Assign role
            $role = Database::fetch("SELECT id FROM roles WHERE tenant_id = ? AND slug = ?", [$token['tenant_id'], $token['role_slug']]);
            if ($role) {
                UserModel::assignRole($userId, $role['id'], $token['tenant_id']);
            }
        }
        
        // Mark token as used
        Database::execute("UPDATE invitation_tokens SET accepted_at = ? WHERE id = ?", [date('Y-m-d H:i:s'), $token['id']]);
        
        AuditLog::log('Activated Account', 'user', $userId, "Account activated via invitation");
        
        flash('success', 'Account activated successfully. Please log in.');
        redirect('/login');
    }
}
