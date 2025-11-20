import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../services/auth_service.dart';
import '../widgets/modern_loading.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  final VoidCallback? onThemeToggle;
  
  const LoginScreen({super.key, this.onThemeToggle});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  bool isLogin = true;                 // toggle between login & sign-up
  String email = '';
  String password = '';
  String firstName = '';
  String lastName = '';
  String phone = '';
  String address = '';
  String barangay = '';
  String city = '';
  bool _isLoading = false;
  final AuthService _auth = AuthService();
  bool _rememberMe = true;

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    _formKey.currentState!.save();
    setState(() { _isLoading = true; });
    try {
      if (isLogin) {
        final data = await _auth.login(email: email, password: password, persist: _rememberMe);
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Login successful')),
        );
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => HomeScreen(onThemeToggle: widget.onThemeToggle)),
        );
      } else {
        await _auth.signup(firstName: firstName, lastName: lastName, email: email, password: password, phone: phone, address: address, barangay: barangay, city: city);
        if (!mounted) return;
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => HomeScreen(onThemeToggle: widget.onThemeToggle)),
        );
      }
    } catch (e) {
      if (!mounted) return;
      final errorTitle = isLogin ? 'Login Failed' : 'Signup Failed';
      showDialog(
        context: context,
        builder: (context) => ModernErrorDialog(
          title: errorTitle,
          message: _getErrorMessage(e.toString()),
        ),
      );
    } finally {
      if (mounted) setState(() { _isLoading = false; });
    }
  }

  String _getErrorMessage(String error) {
    // Extract the actual error message from AuthException
    String message = error;
    if (error.contains('AuthException')) {
      // Extract message from AuthException format: "AuthException(statusCode): message"
      final match = RegExp(r'AuthException\([^)]*\):\s*(.+)').firstMatch(error);
      if (match != null) {
        message = match.group(1) ?? error;
      }
    }
    
    if (message.contains('SocketException') || message.contains('Connection refused') || message.contains('Cannot connect to server')) {
      return 'Cannot connect to server. Please check:\n\n• Your internet connection\n• XAMPP is running\n• API URL is correct (currently: ${const String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api')})';
    } else if (message.contains('FormatException') || message.contains('Invalid server response')) {
      return 'Server returned invalid response. Please check if the API endpoint is correct and the server is responding properly.';
    } else if (message.contains('timeout') || message.contains('Connection timeout')) {
      return 'Connection timeout. Please check your internet connection and try again.';
    } else if (message.contains('Invalid email or password')) {
      return 'Invalid email or password. Please check your credentials and try again.';
    } else {
      return message.isNotEmpty ? message : 'Login failed. Please try again.';
    }
  }

  Future<void> _forgotPassword() async {
    final emailController = TextEditingController();
    bool isLoading = false;

    final email = await showDialog<String>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          title: const Text('Reset Password'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text('Enter your email to receive reset instructions'),
              const SizedBox(height: 16),
              TextField(
                controller: emailController,
                decoration: const InputDecoration(
                  labelText: 'Email',
                  prefixIcon: Icon(Icons.email),
                ),
                keyboardType: TextInputType.emailAddress,
                enabled: !isLoading,
              ),
              if (isLoading) ...[
                const SizedBox(height: 20),
                const ModernLoading(size: 40, message: 'Sending reset link...'),
              ],
            ],
          ),
          actions: [
            TextButton(
              onPressed: isLoading ? null : () => Navigator.pop(context), 
              child: const Text('Cancel')
            ),
            ElevatedButton(
              onPressed: isLoading ? null : () async {
                setState(() => isLoading = true);
                
                try {
                  // Call forgot password API
                  const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
                  final url = Uri.parse('$base/forgot_password.php');
                  
                  final response = await http.post(
                    url,
                    headers: {'Content-Type': 'application/json'},
                    body: jsonEncode({'email': emailController.text}),
                  );

                  final data = jsonDecode(response.body) as Map<String, dynamic>;
                  if (response.statusCode == 200 && data['success'] == true) {
                    Navigator.pop(context, emailController.text);
                    
                    // Check if reset link is provided (for testing)
                    if (data['reset_link'] != null) {
                      showDialog(
                        context: context,
                        builder: (context) => AlertDialog(
                          title: const Text('Password Reset Link'),
                          content: Column(
                            mainAxisSize: MainAxisSize.min,
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Hello ${data['user_name']}!'),
                              const SizedBox(height: 10),
                              const Text('Copy this link and open it in your browser to reset your password:'),
                              const SizedBox(height: 10),
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: Colors.grey.shade100,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: SelectableText(
                                  data['reset_link'],
                                  style: const TextStyle(fontSize: 12),
                                ),
                              ),
                              const SizedBox(height: 10),
                              Text('Expires in: ${data['expires_in']}'),
                            ],
                          ),
                          actions: [
                            TextButton(
                              onPressed: () => Navigator.pop(context),
                              child: const Text('OK'),
                            ),
                          ],
                        ),
                      );
                    } else {
                      // Normal email sent message
                      showDialog(
                        context: context,
                        builder: (context) => const ModernSuccessDialog(
                          title: 'Email Sent!',
                          message: 'Password reset link has been sent to your email',
                        ),
                      );
                    }
                  } else {
                    showDialog(
                      context: context,
                      builder: (context) => ModernErrorDialog(
                        title: 'Error',
                        message: data['message'] ?? 'Failed to send reset link',
                      ),
                    );
                  }
                } catch (e) {
                  showDialog(
                    context: context,
                    builder: (context) => ModernErrorDialog(
                      title: 'Error',
                      message: 'An error occurred: $e',
                    ),
                  );
                } finally {
                  setState(() => isLoading = false);
                }
              },
              child: isLoading 
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                      ),
                    )
                  : const Text('Send'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(24, 16, 24, 60),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(height: 8),
              Icon(Icons.recycling, size: 88, color: Theme.of(context).colorScheme.primary),
              const SizedBox(height: 12),
              Text('MaBote.ph', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700, color: Theme.of(context).colorScheme.primary)),
              const SizedBox(height: 24),
              _Segmented(isLogin: isLogin, onChanged: (v) => setState(() => isLogin = v)),
              const SizedBox(height: 20),
              Form(
                key: _formKey,
                child: Column(
                  children: [
                    if (!isLogin) ...[
                      _roundedField(
                        context,
                        label: 'First name',
                        icon: Icons.person_outline,
                        onSaved: (v) => firstName = v!.trim(),
                        validator: (v) => isLogin || (v != null && v.trim().isNotEmpty) ? null : 'Required',
                      ),
                      const SizedBox(height: 14),
                      _roundedField(
                        context,
                        label: 'Last name',
                        icon: Icons.person_2_outlined,
                        onSaved: (v) => lastName = v!.trim(),
                        validator: (v) => isLogin || (v != null && v.trim().isNotEmpty) ? null : 'Required',
                      ),
                      const SizedBox(height: 14),
                      _roundedField(
                        context,
                        label: 'Phone',
                        icon: Icons.phone_outlined,
                        keyboardType: TextInputType.phone,
                        onSaved: (v) => phone = v?.trim() ?? '',
                      ),
                      const SizedBox(height: 14),
                      _roundedField(
                        context,
                        label: 'Address',
                        icon: Icons.location_on_outlined,
                        onSaved: (v) => address = v?.trim() ?? '',
                      ),
                      const SizedBox(height: 14),
                      _roundedField(
                        context,
                        label: 'Barangay',
                        icon: Icons.location_city,
                        onSaved: (v) => barangay = v?.trim() ?? '',
                      ),
                      const SizedBox(height: 14),
                      _roundedField(
                        context,
                        label: 'City',
                        icon: Icons.apartment_outlined,
                        onSaved: (v) => city = v?.trim() ?? '',
                      ),
                      const SizedBox(height: 14),
                    ],
                    _roundedField(
                      context,
                      label: 'Email',
                      icon: Icons.alternate_email,
                      keyboardType: TextInputType.emailAddress,
                      validator: (v) => v != null && v.contains('@') ? null : 'Enter a valid email',
                      onSaved: (v) => email = v!.trim(),
                    ),
                    const SizedBox(height: 14),
                    _roundedField(
                      context,
                      label: 'Password',
                      icon: Icons.lock_outline,
                      obscureText: true,
                      validator: (v) => v != null && v.length >= 6 ? null : 'Min 6 characters',
                      onSaved: (v) => password = v!.trim(),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Checkbox(value: _rememberMe, onChanged: (v) => setState(() => _rememberMe = v ?? true)),
                        const Text('Remember me', style: TextStyle(fontSize: 12)),
                        const Spacer(),
                        TextButton(
                          onPressed: () => _forgotPassword(), 
                          child: const Text('Forgot password?', style: TextStyle(fontSize: 12)),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _isLoading ? null : _submit,
                        child: _isLoading
                            ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                            : Text(isLogin ? 'Login' : 'Sign Up'),
                      ),
                    ),
                    const SizedBox(height: 4),
                    TextButton(
                      onPressed: () => setState(() => isLogin = !isLogin),
                      child: Text(isLogin ? 'No account? Sign Up' : 'Already have an account? Login'),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

Widget _roundedField(
  BuildContext context, {
  required String label,
  required IconData icon,
  bool obscureText = false,
  TextInputType? keyboardType,
  String? Function(String?)? validator,
  void Function(String?)? onSaved,
}) {
  return TextFormField(
    decoration: InputDecoration(
      labelText: label,
      prefixIcon: Icon(icon, color: Theme.of(context).colorScheme.primary),
    ),
    obscureText: obscureText,
    keyboardType: keyboardType,
    validator: validator,
    onSaved: onSaved,
  );
}

class _Segmented extends StatelessWidget {
  const _Segmented({required this.isLogin, required this.onChanged});
  final bool isLogin;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    final bg = Theme.of(context).colorScheme.surface;
    final active = Theme.of(context).colorScheme.primary;
    return Container(
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFDDE9D9)),
      ),
      child: Row(
        children: [
          _segButton(context, label: 'Login', selected: isLogin, onTap: () => onChanged(true), active: active),
          _segButton(context, label: 'Sign Up', selected: !isLogin, onTap: () => onChanged(false), active: active),
        ],
      ),
    );
  }

  Expanded _segButton(BuildContext context, {required String label, required bool selected, required VoidCallback onTap, required Color active}) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: selected ? const Color(0xFFDBF2D3) : Colors.transparent,
            borderRadius: BorderRadius.circular(20),
          ),
          alignment: Alignment.center,
          child: Text(
            label,
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: selected ? active : Colors.grey[600],
            ),
          ),
        ),
      ),
    );
  }
}
