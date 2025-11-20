import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';

import '../services/session.dart';
import '../widgets/modern_loading.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Future<Map<String, dynamic>>? _future;
  bool _isEditing = false;
  bool _isSaving = false;
  bool _isUploadingImage = false;
  final _formKey = GlobalKey<FormState>();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _barangayController = TextEditingController();
  final _cityController = TextEditingController();
  File? _selectedImage;

  @override
  void initState() {
    super.initState();
    _future = _fetch();
  }

  Future<Map<String, dynamic>> _fetch() async {
    final uid = await Session.userId();
    if (uid == null) throw Exception('Not logged in');
    const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
    final url = Uri.parse('$base/get_profile.php?user_id=$uid');
    final res = await http.get(url);
    final data = jsonDecode(res.body) as Map<String, dynamic>;
    if (res.statusCode == 200 && data['success'] == true) {
      return data['user'] as Map<String, dynamic>;
    }
    throw Exception(data['message'] ?? 'Failed to fetch profile');
  }

  Future<void> _updateProfile() async {
    if (!_formKey.currentState!.validate()) return;
    
    setState(() => _isSaving = true);

    try {
      final uid = await Session.userId();
      if (uid == null) return;

      const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
      final url = Uri.parse('$base/update_profile.php');
      final res = await http.post(
        url,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'user_id': uid,
          'first_name': _firstNameController.text.trim(),
          'last_name': _lastNameController.text.trim(),
          'phone': _phoneController.text.trim(),
          'address': _addressController.text.trim(),
          'barangay': _barangayController.text.trim(),
          'city': _cityController.text.trim(),
        }),
      );
      
      final data = jsonDecode(res.body) as Map<String, dynamic>;
      if (res.statusCode == 200 && data['success'] == true) {
        // Update session with new name
        await Session.save(
          userId: uid,
          userName: '${_firstNameController.text.trim()} ${_lastNameController.text.trim()}',
          userEmail: await Session.userEmail() ?? '',
          token: await Session.token() ?? '',
        );
        
        setState(() => _isEditing = false);
        
        if (mounted) {
          showDialog(
            context: context,
            builder: (context) => const ModernSuccessDialog(
              title: 'Success!',
              message: 'Your profile has been updated successfully!',
            ),
          );
        }
      } else {
        if (mounted) {
          showDialog(
            context: context,
            builder: (context) => ModernErrorDialog(
              title: 'Error',
              message: data['message'] ?? 'Failed to update profile',
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        showDialog(
          context: context,
          builder: (context) => ModernErrorDialog(
            title: 'Error',
            message: 'An error occurred: $e',
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isSaving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Profile')),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const ModernLoading(message: 'Loading profile...');
          }
          if (snapshot.hasError) {
            return Center(child: Text(snapshot.error.toString()));
          }
          final user = snapshot.data!;
          
          // Always populate controllers with user data
          _firstNameController.text = user['first_name'] ?? '';
          _lastNameController.text = user['last_name'] ?? '';
          _phoneController.text = user['phone'] ?? '';
          _addressController.text = user['address'] ?? '';
          _barangayController.text = user['barangay'] ?? '';
          _cityController.text = user['city'] ?? '';

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Form(
              key: _formKey,
              child: Column(
                children: [
                  GestureDetector(
                    onTap: () => _showImagePicker(context),
                    child: CircleAvatar(
                      radius: 50,
                      backgroundColor: Colors.lightBlue,
                      backgroundImage: user['user_profile'] != null 
                          ? NetworkImage('http://192.168.254.128/mabote_api/${user['user_profile']}') 
                          : null,
                      child: user['user_profile'] == null
                          ? Text(
                              '${(user['first_name']?[0] ?? 'U').toUpperCase()}',
                              style: const TextStyle(fontSize: 32, color: Colors.white, fontWeight: FontWeight.bold),
                            )
                          : null,
                    ),
                  ),
                  const SizedBox(height: 24),
                  Row(
                    children: [
                      Expanded(child: _buildField('First Name', _firstNameController, enabled: _isEditing, required: true)),
                      const SizedBox(width: 12),
                      Expanded(child: _buildField('Last Name', _lastNameController, enabled: _isEditing, required: true)),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _buildField('Email', TextEditingController(text: user['email'] ?? ''), enabled: false),
                  const SizedBox(height: 16),
                  _buildField('Phone', _phoneController, enabled: _isEditing),
                  const SizedBox(height: 16),
                  _buildField('Address', _addressController, enabled: _isEditing, maxLines: 2),
                  const SizedBox(height: 16),
                  _buildField('Barangay', _barangayController, enabled: _isEditing),
                  const SizedBox(height: 16),
                  _buildField('City', _cityController, enabled: _isEditing),
                  const SizedBox(height: 24),
                  const SizedBox(height: 12),
                  if (!_isEditing) ...[
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () => setState(() => _isEditing = true),
                        child: const Text('Edit Profile'),
                      ),
                    ),
                  ] else ...[
                    Row(
                      children: [
                        Expanded(
                          child: ElevatedButton(
                            onPressed: _isSaving ? null : _updateProfile,
                            child: _isSaving 
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                                    ),
                                  )
                                : const Text('Save'),
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: OutlinedButton(
                            onPressed: () => setState(() => _isEditing = false),
                            child: const Text('Cancel'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildField(String label, TextEditingController controller, {bool enabled = true, bool required = false, int maxLines = 1}) {
    return TextFormField(
      controller: controller,
      enabled: enabled,
      maxLines: maxLines,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(_getIconForField(label), color: Colors.lightBlue),
      ),
      validator: required ? (v) => v?.trim().isEmpty == true ? 'Required' : null : null,
    );
  }

  IconData _getIconForField(String label) {
    switch (label) {
      case 'First Name':
      case 'Last Name':
        return Icons.person;
      case 'Email':
        return Icons.email;
      case 'Phone':
        return Icons.phone;
      case 'Address':
        return Icons.location_on;
      case 'Barangay':
        return Icons.location_city;
      case 'City':
        return Icons.location_city;
      default:
        return Icons.info;
    }
  }

  Future<void> _showImagePicker(BuildContext context) async {
    showModalBottomSheet(
      context: context,
      builder: (context) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.camera_alt),
              title: const Text('Camera'),
              onTap: () {
                Navigator.pop(context);
                _pickImage(ImageSource.camera);
              },
            ),
            ListTile(
              leading: const Icon(Icons.photo_library),
              title: const Text('Gallery'),
              onTap: () {
                Navigator.pop(context);
                _pickImage(ImageSource.gallery);
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _pickImage(ImageSource source) async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(source: source);
    if (pickedFile != null) {
      setState(() {
        _selectedImage = File(pickedFile.path);
      });
      
      // Upload image to server
      await _uploadProfileImage(File(pickedFile.path));
    }
  }

  Future<void> _uploadProfileImage(File imageFile) async {
    setState(() => _isUploadingImage = true);

    try {
      final userId = await Session.userId();
      if (userId == null) return;

      const base = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://192.168.254.128/mabote_api');
      final url = Uri.parse('$base/upload_profile_image.php');
      
      var request = http.MultipartRequest('POST', url);
      request.fields['user_id'] = userId.toString();
      request.files.add(await http.MultipartFile.fromPath('profile_image', imageFile.path));
      
      var response = await request.send();
      final responseBody = await response.stream.bytesToString();
      final data = jsonDecode(responseBody) as Map<String, dynamic>;
      
      if (response.statusCode == 200 && data['success'] == true) {
        if (mounted) {
          showDialog(
            context: context,
            builder: (context) => const ModernSuccessDialog(
              title: 'Success!',
              message: 'Your profile picture has been updated successfully!',
            ),
          );
        }
        // Refresh profile data
        setState(() {
          _future = _fetch();
        });
      } else {
        if (mounted) {
          showDialog(
            context: context,
            builder: (context) => ModernErrorDialog(
              title: 'Upload Failed',
              message: data['message'] ?? 'Failed to upload image',
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        showDialog(
          context: context,
          builder: (context) => ModernErrorDialog(
            title: 'Upload Error',
            message: 'An error occurred: $e',
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isUploadingImage = false);
      }
    }
  }
}
