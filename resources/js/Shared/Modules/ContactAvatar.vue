<template>
  <div>
    <div
      v-if="data.avatar.type === 'svg'"
      @click="handleAvatarClick"
      :class="[
        'mx-auto mb-6 w-32 sm:w-1/2 cursor-pointer transition-opacity hover:opacity-75',
        data.canUploadFile ? 'hover:ring-2 hover:ring-blue-500 hover:ring-offset-2 rounded-xs' : '',
      ]"
      :title="data.canUploadFile ? 'Click to upload avatar' : ''">
      <avatar
        :data="data.avatar"
        :class="'pointer-events-none'"
        :img-classes="'rounded-xs sm:w-72 pointer-events-none'" />
    </div>
    <avatar v-else :data="data.avatar" :class="'mx-auto mb-6 w-32 sm:w-1/2'" :img-classes="'rounded-xs sm:w-72'" />
  </div>
</template>

<script>
import Avatar from '@/Shared/Avatar.vue';

export default {
  components: {
    Avatar,
  },

  props: {
    data: {
      type: Object,
      default: null,
    },
  },

  emits: ['avatar-click'],

  methods: {
    handleAvatarClick() {
      if (this.data.canUploadFile) {
        this.$emit('avatar-click');
      }
    },
  },
};
</script>
