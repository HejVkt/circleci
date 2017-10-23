<template>
    <button type="submit" :class="classes" @click="toggle">
        <span class="glyphicon glyphicon-heart"></span>
        <span v-text="count"></span>
    </button>
</template>

<script>
    export default {

        props:['reply'],

        data(){
            return {
                count: this.reply.favoritesCount,
                active: this.reply.isFavorited
            }
        },

        computed:{
            classes(){
                return ['btn', this.active ? 'btn-primary' : 'btn-default'];
            },
            path(){
                return '/replies/' + this.reply.id + '/favorites';
            }
        },

        methods:{
            toggle(){
                return this.active ? this.destoy() : this.create();
            },

            destoy(){
                axios.delete(this.path);

                this.active = false;
                this.count--;
            },

            create(){

                axios.post(this.path);

                this.active = true;
                this.count++;

            },
        }
    }
</script>
