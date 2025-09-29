<template>
  <div>
    <input
      ref="fileInput"
      type="file"
      :accept="acceptedTypes"
      :multiple="multiple"
      style="display: none"
      @change="handleFileSelect" />

    <div @click="openFilePicker" class="cursor-pointer">
      <slot>
        <div
          class="flex items-center justify-center px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          Upload File
        </div>
      </slot>
    </div>

    <!-- Upload Progress -->
    <div v-if="uploading" class="mt-2">
      <div class="bg-gray-200 rounded-full h-2">
        <div
          class="bg-blue-500 h-2 rounded-full transition-all duration-300"
          :style="`width: ${uploadProgress}%`"></div>
      </div>
      <p class="text-sm text-gray-600 mt-1">{{ uploadProgress }}% uploaded</p>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="mt-2 text-sm text-red-600">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { ref, defineEmits, defineProps } from 'vue';
import axios from 'axios';

const props = defineProps({
  uploadUrl: {
    type: String,
    required: true,
  },
  httpMethod: {
    type: String,
    default: 'POST',
  },
  acceptedTypes: {
    type: String,
    default: 'image/*',
  },
  multiple: {
    type: Boolean,
    default: false,
  },
  maxFileSize: {
    type: Number,
    default: 10 * 1024 * 1024, // 10MB default
  },
});

const emit = defineEmits(['success', 'error', 'progress']);

const fileInput = ref(null);
const uploading = ref(false);
const uploadProgress = ref(0);
const error = ref('');

const openFilePicker = () => {
  fileInput.value?.click();
};

const handleFileSelect = (event) => {
  const files = event.target.files;
  if (!files || files.length === 0) return;

  const file = files[0]; // Handle single file for now

  // Validate file size
  if (file.size > props.maxFileSize) {
    error.value = `File size must be less than ${(props.maxFileSize / 1024 / 1024).toFixed(1)}MB`;
    return;
  }

  // Validate file type
  if (props.acceptedTypes !== '*/*') {
    const acceptedTypesArray = props.acceptedTypes.split(',').map((type) => type.trim());
    const isValidType = acceptedTypesArray.some((acceptedType) => {
      if (acceptedType === '*/*') return true;
      if (acceptedType.endsWith('/*')) {
        const mainType = acceptedType.split('/')[0];
        return file.type.startsWith(mainType + '/');
      }
      return file.type === acceptedType;
    });

    if (!isValidType) {
      error.value = 'Invalid file type';
      return;
    }
  }

  uploadFile(file);
};

const uploadFile = async (file) => {
  const formData = new FormData();
  formData.append('file', file);

  // For PUT requests, add Laravel method spoofing
  if (props.httpMethod.toUpperCase() === 'PUT') {
    formData.append('_method', 'PUT');
  }

  uploading.value = true;
  uploadProgress.value = 0;
  error.value = '';

  try {
    const response = await axios.post(props.uploadUrl, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (progressEvent) => {
        if (progressEvent.lengthComputable) {
          uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
          emit('progress', uploadProgress.value);
        }
      },
    });

    // Emit success with file data
    emit('success', {
      file: file,
      response: response.data,
      url: response.data.url || response.data.path,
      name: file.name,
      size: file.size,
      type: file.type,
    });

    // Reset file input
    fileInput.value.value = '';
  } catch (err) {
    error.value = err.response?.data?.message || 'Upload failed';
    emit('error', err);
  } finally {
    uploading.value = false;
    uploadProgress.value = 0;
  }
};

// Expose methods for parent components
defineExpose({
  openFilePicker,
});
</script>
