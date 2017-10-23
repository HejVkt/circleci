<template>
    <div>

        <div class="flex">
            <h1>
                {{ user.name }}
            </h1>

            <img :src="avatar" width="150" height="150" />

        </div><br/>
        <form v-if="canUpdate"  method="post" enctype="multipart/form-data">
            <image-upload name="avatar" @loaded="onLoad"></image-upload>
        </form>
    </div>
</template>
<script>

    import ImageUpload from './ImageUpload.vue';

export default {
    data(){
        return {
            avatar: this.user.avatar_path
        }
    },

    components: {ImageUpload},

    props: ['user'],


    computed:{
        canUpdate(){
            return this.authorize(user => user.id === this.user.id );
        }
    },

    methods:{

        onLoad(data){
            this.avatar = data.src;
            this.persist(data.file);
        },

        persist(file){
            let data = new FormData();
            data.append('avatar', file);
            axios.post('/api/users/'+this.user.name+'/avatar', data).then(()=>{
                flash('Your avatar have been uploaded');
            });
        }
    }
}
</script>